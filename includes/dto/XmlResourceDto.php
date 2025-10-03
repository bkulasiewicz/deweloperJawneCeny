<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

class XmlResourceDto extends ModelDto {
    
    public const FIELD_ID = 'id';
    public const FIELD_EXT_IDENT = 'ext_ident';
    public const FIELD_CSV_URL = 'csv_url';
    public const FIELD_DATA_DATE = 'data_date';
    public const FIELD_CREATED_AT = 'created_at';
    
    public int $id;
    public string $ext_ident;
    public string $csv_url;
    public string $data_date;
    public string $created_at;
    
    public function __construct(
        int $id,
        string $ext_ident,
        string $csv_url,
        string $data_date,
        string $created_at
    ) {
        $this->id = $id;
        $this->ext_ident = $ext_ident;
        $this->csv_url = $csv_url;
        $this->data_date = $data_date;
        $this->created_at = $created_at;
    }
    
    public static function databaseToModel(array $data): static {
        return new static(
            (int)$data[self::FIELD_ID],
            $data[self::FIELD_EXT_IDENT] ?? '',
            $data[self::FIELD_CSV_URL] ?? '',
            $data[self::FIELD_DATA_DATE] ?? '',
            $data[self::FIELD_CREATED_AT] ?? ''
        );
    }
    
    public function modelToDatabase(): array {
        return [
            self::FIELD_EXT_IDENT => $this->ext_ident,
            self::FIELD_CSV_URL => $this->csv_url,
            self::FIELD_DATA_DATE => $this->data_date,
            self::FIELD_CREATED_AT => $this->created_at
        ];
    }
}