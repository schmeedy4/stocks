<?php

declare(strict_types=1);

class InstrumentPriceService
{
    private InstrumentPriceDailyRepository $price_repo;
    private TradeLotRepository $lot_repo;
    private InstrumentRepository $instrument_repo;
    private string $api_key;

    public function __construct()
    {
        $this->price_repo = new InstrumentPriceDailyRepository();
        $this->lot_repo = new TradeLotRepository();
        $this->instrument_repo = new InstrumentRepository();
        
        $config = require __DIR__ . '/../config/config.php';
        $this->api_key = $config['twelvedata']['api_key'] ?? '';
    }

    /**
     * Get list of instruments with open positions for the user.
     * Returns array of ['instrument' => Instrument, 'latest_price' => InstrumentPriceDaily|null]
     */
    public function list_portfolio_instruments(int $user_id): array
    {
        // Get instruments with open positions
        $instruments_with_qty = $this->lot_repo->get_instruments_with_availability($user_id, null, null, false);
        
        $result = [];
        foreach ($instruments_with_qty as $item) {
            $instrument = $this->instrument_repo->find_by_id($item['instrument_id']);
            if ($instrument === null) {
                continue;
            }

            $latest_price = $this->price_repo->get_latest_price($user_id, $instrument->id);
            
            $result[] = [
                'instrument' => $instrument,
                'latest_price' => $latest_price,
            ];
        }

        return $result;
    }

    /**
     * Update prices for portfolio instruments.
     * Returns array with counts: ['updated' => int, 'skipped' => int, 'failed' => int, 'errors' => array]
     */
    public function update_prices(int $user_id, string $price_date, bool $force_update = false): array
    {
        $instruments_with_qty = $this->lot_repo->get_instruments_with_availability($user_id, null, null, false);
        
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        foreach ($instruments_with_qty as $item) {
            $instrument_id = $item['instrument_id'];
            $instrument = $this->instrument_repo->find_by_id($instrument_id);
            
            if ($instrument === null || $instrument->ticker === null || $instrument->ticker === '') {
                $failed++;
                $errors[] = "Instrument ID {$instrument_id}: No ticker symbol";
                continue;
            }

            // Check if price already exists for this date
            if (!$force_update) {
                $existing = $this->price_repo->find_by_date($user_id, $instrument_id, $price_date);
                if ($existing !== null) {
                    $skipped++;
                    continue;
                }
            }

            // Fetch price from API
            try {
                $price_data = $this->fetch_price_from_api($instrument->ticker);
                
                if ($price_data === null) {
                    $failed++;
                    $errors[] = "{$instrument->ticker}: Failed to fetch price from API";
                    continue;
                }

                // Store price
                $this->price_repo->create($user_id, [
                    'instrument_id' => $instrument_id,
                    'price_date' => $price_date,
                    'close_price' => $price_data['close_price'],
                    'currency' => $price_data['currency'],
                    'source' => 'twelvedata',
                ]);

                $updated++;

                // Throttle: sleep ~8 seconds between API calls (8 requests/min limit)
                sleep(8);

            } catch (\Exception $e) {
                $failed++;
                $errors[] = "{$instrument->ticker}: " . $e->getMessage();
                // Continue to next instrument
            }
        }

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Update prices for last 5 days from Twelve Data API time series.
     * Returns array with counts: ['symbols_updated' => int, 'symbols_failed' => int, 'rows_upserted' => int, 'errors' => array]
     */
    public function update_last_5_days(int $user_id): array
    {
        $instruments_with_qty = $this->lot_repo->get_instruments_with_availability($user_id, null, null, false);
        
        $symbols_updated = 0;
        $symbols_failed = 0;
        $rows_upserted = 0;
        $errors = [];
        $min_date = null;
        $max_date = null;

        foreach ($instruments_with_qty as $item) {
            $instrument_id = $item['instrument_id'];
            $instrument = $this->instrument_repo->find_by_id($instrument_id);
            
            if ($instrument === null || $instrument->ticker === null || $instrument->ticker === '') {
                $symbols_failed++;
                $errors[] = "Instrument ID {$instrument_id}: No ticker symbol";
                continue;
            }

            // Skip private instruments
            if ($instrument->is_private) {
                continue;
            }

            try {
                $time_series_data = $this->fetch_time_series_from_api($instrument->ticker);
                
                if ($time_series_data === null || empty($time_series_data['values'])) {
                    $symbols_failed++;
                    $errors[] = "{$instrument->ticker}: Failed to fetch time series from API";
                    continue;
                }

                $currency = $time_series_data['currency'] ?? 'USD';

                // Upsert each returned value
                foreach ($time_series_data['values'] as $value) {
                    if (!isset($value['datetime']) || !isset($value['close'])) {
                        continue; // Skip invalid rows
                    }

                    $datetime = $value['datetime'];
                    
                    // Track min and max datetime
                    if ($min_date === null || $datetime < $min_date) {
                        $min_date = $datetime;
                    }
                    if ($max_date === null || $datetime > $max_date) {
                        $max_date = $datetime;
                    }

                    $this->price_repo->upsert($user_id, [
                        'instrument_id' => $instrument_id,
                        'price_date' => $datetime,
                        'open_price' => isset($value['open']) ? (string) $value['open'] : null,
                        'high_price' => isset($value['high']) ? (string) $value['high'] : null,
                        'low_price' => isset($value['low']) ? (string) $value['low'] : null,
                        'close_price' => (string) $value['close'],
                        'volume' => isset($value['volume']) ? (string) $value['volume'] : null,
                        'currency' => $currency,
                        'source' => 'twelvedata',
                    ]);

                    $rows_upserted++;
                }

                $symbols_updated++;

                // Throttle: sleep ~8 seconds between SYMBOL requests (8 requests/min limit)
                sleep(8);

            } catch (\Exception $e) {
                $symbols_failed++;
                $errors[] = "{$instrument->ticker}: " . $e->getMessage();
                // Continue to next instrument
            }
        }

        return [
            'symbols_updated' => $symbols_updated,
            'symbols_failed' => $symbols_failed,
            'rows_upserted' => $rows_upserted,
            'errors' => $errors,
            'min_date' => $min_date,
            'max_date' => $max_date,
        ];
    }

    /**
     * Fetch latest price from Twelve Data API.
     * Returns array ['close_price' => string, 'currency' => string] or null on error.
     */
    private function fetch_price_from_api(string $symbol): ?array
    {
        if ($this->api_key === '') {
            throw new \RuntimeException('Twelve Data API key not configured');
        }

        $url = "https://api.twelvedata.com/time_series?apikey={$this->api_key}&symbol={$symbol}&interval=1day&outputsize=1";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            // Disable SSL verification for local development (WAMP/XAMPP environments)
            // In production, configure proper CA certificate bundle instead
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);

        if ($curl_error !== '') {
            throw new \RuntimeException("cURL error: {$curl_error}");
        }

        if ($http_code !== 200) {
            throw new \RuntimeException("HTTP error: {$http_code}");
        }

        $data = json_decode($response, true);
        
        if (!is_array($data) || !isset($data['status']) || $data['status'] !== 'ok') {
            throw new \RuntimeException('API returned error status');
        }

        if (!isset($data['values']) || !is_array($data['values']) || empty($data['values'])) {
            throw new \RuntimeException('No price data in API response');
        }

        // Get the first (latest) value
        $latest = $data['values'][0];
        
        if (!isset($latest['close'])) {
            throw new \RuntimeException('Missing close price in API response');
        }

        $currency = $data['meta']['currency'] ?? 'USD';

        return [
            'close_price' => (string) $latest['close'],
            'currency' => $currency,
        ];
    }

    /**
     * Fetch time series (last 5 days) from Twelve Data API.
     * Returns array ['values' => array, 'currency' => string] or null on error.
     */
    private function fetch_time_series_from_api(string $symbol): ?array
    {
        if ($this->api_key === '') {
            throw new \RuntimeException('Twelve Data API key not configured');
        }

        $url = "https://api.twelvedata.com/time_series?apikey={$this->api_key}&symbol={$symbol}&interval=1day&outputsize=5";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            // Disable SSL verification for local development (WAMP/XAMPP environments)
            // In production, configure proper CA certificate bundle instead
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);

        if ($curl_error !== '') {
            throw new \RuntimeException("cURL error: {$curl_error}");
        }

        if ($http_code !== 200) {
            throw new \RuntimeException("HTTP error: {$http_code}");
        }

        $data = json_decode($response, true);
        
        if (!is_array($data) || !isset($data['status']) || $data['status'] !== 'ok') {
            throw new \RuntimeException('API returned error status');
        }

        if (!isset($data['values']) || !is_array($data['values']) || empty($data['values'])) {
            throw new \RuntimeException('No price data in API response');
        }

        $currency = $data['meta']['currency'] ?? 'USD';

        return [
            'values' => $data['values'],
            'currency' => $currency,
        ];
    }
}

