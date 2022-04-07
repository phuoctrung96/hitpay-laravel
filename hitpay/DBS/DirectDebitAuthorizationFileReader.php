<?php

namespace HitPay\DBS;

use Exception;
use Illuminate\Support\Str;

class DirectDebitAuthorizationFileReader
{
    private $content;

    private $header;

    private $records;

    private $footer;

    /**
     * DirectDebitAuthorizationFileReader constructor.
     *
     * @param string $content
     *
     * @throws \Exception
     */
    private function __construct(string $content)
    {
        $this->content = $content;

        $this->analyse();
    }

    /**
     * Read and analyse the DDA file.
     *
     * @throws \Exception
     */
    private function analyse()
    {
        $file = explode("\r\n", $this->content);

        // RP-3d69cb5fb73247cebfb0767e5816f751 << This is the payment reference, we create it later
        // RP-20201231-ABCDEFGH << this is the DDA

        // We remove the header and footer from the file first and keep the record to be processed next in the foreach
        // loop. We will process the header and footer after the records is analysed.

        $headerLine = array_shift($file);

        $offset = 0;

        $header['record_type'] = $this->nextValue($headerLine, 2, $offset);
        $header['creation_date'] = $this->nextValue($headerLine, 8, $offset);
        $header['sender_company_id'] = $this->nextValue($headerLine, 8, $offset);
        $header['originating_bank_identifier_code'] = $this->nextValue($headerLine, 35, $offset);
        $header['originating_account_number'] = $this->nextValue($headerLine, 34, $offset);
        $header['originator_name'] = $this->nextValue($headerLine, 20, $offset);

        $offset += 120;

        $header['message_sequence_number'] = $this->nextValue($headerLine, 5, $offset);

        // TODO - Validate header.

        $this->header = $this->trimAll($header);

        if ($header['record_type'] !== '01') {
            throw new Exception('Invalid file, the file should start its first line with 01.');
        }

        foreach (array_values($file) as $index => $line) {
            if (isset($footerLine)) {
                if (trim($line) === '') {
                    continue;
                } else {
                    throw new Exception('Invalid file, the footer set, but still have non-empty line after that.');
                }
            }

            if (Str::startsWith($line, '10')) {
                $this->records[] = $this->read($line);
            } elseif (Str::startsWith($line, '20')) {
                $footerLine = $line;
            } else {
                throw new Exception('Invalid file, the line looks invalid but footer not set yet.');
            }
        }

        if (!isset($footerLine)) {
            throw new Exception('Invalid file, footer not detected.');
        }

        $offset = 0;

        $footer['record_type'] = $this->nextValue($footerLine, 2, $offset);
        $footer['total_number_of_successful_applications'] = $this->nextValue($footerLine, 8, $offset);
        $footer['total_number_of_successful_terminations'] = $this->nextValue($footerLine, 8, $offset);
        $footer['total_number_of_successful_amendments'] = $this->nextValue($footerLine, 8, $offset);

        // TODO - Validate footer.

        $this->footer = $this->trimAll($footer);
    }

    private function read(string $line)
    {
        $offset = 0;

        $data['record_type'] = $this->nextValue($line, 2, $offset);
        $data['transaction_type'] = $this->nextValue($line, 1, $offset);
        $data['receiving_bank_identifier_code'] = $this->nextValue($line, 35, $offset);
        $data['receiving_bank_account_number'] = $this->nextValue($line, 34, $offset);
        $data['receiver_name'] = $this->nextValue($line, 20, $offset);

        $offset += 120;

        $data['direct_debit_authorization_reference'] = $this->nextValue($line, 35, $offset);

        // WARNING - The 'payment_limit' returns in smallest value, e.g. $10.00 will be 1000.

        $data['payment_limit'] = $this->nextValue($line, 11, $offset);

        $offset += 55;

        $data['input_mode'] = $this->nextValue($line, 1, $offset);
        $data['nets_terminal_id'] = $this->nextValue($line, 8, $offset);
        $data['retailer_reference_number'] = $this->nextValue($line, 6, $offset);
        $data['transaction_date'] = $this->nextValue($line, 8, $offset);
        $data['transaction_time'] = $this->nextValue($line, 6, $offset);

        $offset += 209;

        // WARNING - The 'new_payment_limit' returns in smallest value, e.g. $10.00 will be 1000.

        $data['new_payment_limit'] = $this->nextValue($line, 11, $offset);

        $offset += 20;

        $data['success_indicator'] = $this->nextValue($line, 1, $offset);
        $data['reason_code'] = $this->nextValue($line, 4, $offset);

        // TODO - Validate data.

        return $this->trimAll($data);
    }

    private function nextValue(string $string, ?int $length, int &$offset)
    {
        $currentOffset = $offset;

        $offset += $length;

        return substr($string, $currentOffset, $length);
    }

    private function trimAll(array $data) : array
    {
        return array_map(function ($value) {
            $value = trim($value);

            return Str::length($value) > 0 ? $value : null;
        }, $data);
    }

    public function getHeader() : array
    {
        return $this->header;
    }

    public function getRecords() : array
    {
        return $this->records;
    }

    public function getFooter() : array
    {
        return $this->footer;
    }

    public static function process(string $content)
    {
        return new static($content);
    }
}
