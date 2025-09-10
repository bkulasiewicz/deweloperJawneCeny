<?php

if (!defined('ABSPATH')) {
    exit;
}

class SupplierDto extends ModelDto {
    
    public const FIELD_ID = 'id';
    public const FIELD_NAZWA = 'name';
    public const FIELD_FORMA_PRAWNA = 'legal_form';
    public const FIELD_NR_KRS = 'krs_number';
    public const FIELD_NR_CEIDG = 'ceidg_number';
    public const FIELD_NR_NIP = 'tax_number';
    public const FIELD_NR_REGON = 'regon_number';
    public const FIELD_TELEFON = 'phone';
    public const FIELD_EMAIL = 'email';
    public const FIELD_FAX = 'fax';
    public const FIELD_STRONA_WWW = 'website';
    
    public const FIELD_SIEDZ_WOJEWODZTWO = 'office_province';
    public const FIELD_SIEDZ_POWIAT = 'office_county';
    public const FIELD_SIEDZ_GMINA = 'office_municipality';
    public const FIELD_SIEDZ_MIEJSCOWOSC = 'office_city';
    public const FIELD_SIEDZ_ULICA = 'office_street';
    public const FIELD_SIEDZ_NR = 'office_number';
    public const FIELD_SIEDZ_LOKAL = 'office_unit';
    public const FIELD_SIEDZ_KOD = 'office_postal_code';
    
    public const FIELD_SPRZED_WOJEWODZTWO = 'sales_province';
    public const FIELD_SPRZED_POWIAT = 'sales_county';
    public const FIELD_SPRZED_GMINA = 'sales_municipality';
    public const FIELD_SPRZED_MIEJSCOWOSC = 'sales_city';
    public const FIELD_SPRZED_ULICA = 'sales_street';
    public const FIELD_SPRZED_NR = 'sales_number';
    public const FIELD_SPRZED_LOKAL = 'sales_unit';
    public const FIELD_SPRZED_KOD = 'sales_postal_code';
    
    public const FIELD_DODATKOWE_LOKALIZACJE = 'additional_locations';
    public const FIELD_SPOSOB_KONTAKTU = 'contact_method';
    
    public readonly int $id;
    public readonly string $nazwa;
    public readonly ?string $forma_prawna;
    public readonly string $nr_krs;
    public readonly ?string $nr_ceidg;
    public readonly string $nr_nip;
    public readonly ?string $nr_regon;
    public readonly ?string $telefon;
    public readonly string $email;
    public readonly ?string $fax;
    public readonly string $strona_www;
    
    public readonly string $siedz_wojewodztwo;
    public readonly ?string $siedz_powiat;
    public readonly ?string $siedz_gmina;
    public readonly string $siedz_miejscowosc;
    public readonly string $siedz_ulica;
    public readonly string $siedz_nr;
    public readonly ?string $siedz_lokal;
    public readonly string $siedz_kod;
    
    public readonly string $sprzed_wojewodztwo;
    public readonly ?string $sprzed_powiat;
    public readonly ?string $sprzed_gmina;
    public readonly string $sprzed_miejscowosc;
    public readonly string $sprzed_ulica;
    public readonly string $sprzed_nr;
    public readonly ?string $sprzed_lokal;
    public readonly string $sprzed_kod;
    
    public readonly ?string $dodatkowe_lokalizacje;
    public readonly ?string $sposob_kontaktu;
    
    public function __construct(
        int $id,
        string $nazwa,
        ?string $forma_prawna,
        string $nr_krs,
        ?string $nr_ceidg,
        string $nr_nip,
        ?string $nr_regon,
        ?string $telefon,
        string $email,
        ?string $fax,
        string $strona_www,
        string $siedz_wojewodztwo,
        ?string $siedz_powiat,
        ?string $siedz_gmina,
        string $siedz_miejscowosc,
        string $siedz_ulica,
        string $siedz_nr,
        ?string $siedz_lokal,
        string $siedz_kod,
        string $sprzed_wojewodztwo,
        ?string $sprzed_powiat,
        ?string $sprzed_gmina,
        string $sprzed_miejscowosc,
        string $sprzed_ulica,
        string $sprzed_nr,
        ?string $sprzed_lokal,
        string $sprzed_kod,
        ?string $dodatkowe_lokalizacje,
        ?string $sposob_kontaktu
    ) {
        $this->id = $id;
        $this->nazwa = $nazwa;
        $this->forma_prawna = $forma_prawna;
        $this->nr_krs = $nr_krs;
        $this->nr_ceidg = $nr_ceidg;
        $this->nr_nip = $nr_nip;
        $this->nr_regon = $nr_regon;
        $this->telefon = $telefon;
        $this->email = $email;
        $this->fax = $fax;
        $this->strona_www = $strona_www;
        $this->siedz_wojewodztwo = $siedz_wojewodztwo;
        $this->siedz_powiat = $siedz_powiat;
        $this->siedz_gmina = $siedz_gmina;
        $this->siedz_miejscowosc = $siedz_miejscowosc;
        $this->siedz_ulica = $siedz_ulica;
        $this->siedz_nr = $siedz_nr;
        $this->siedz_lokal = $siedz_lokal;
        $this->siedz_kod = $siedz_kod;
        $this->sprzed_wojewodztwo = $sprzed_wojewodztwo;
        $this->sprzed_powiat = $sprzed_powiat;
        $this->sprzed_gmina = $sprzed_gmina;
        $this->sprzed_miejscowosc = $sprzed_miejscowosc;
        $this->sprzed_ulica = $sprzed_ulica;
        $this->sprzed_nr = $sprzed_nr;
        $this->sprzed_lokal = $sprzed_lokal;
        $this->sprzed_kod = $sprzed_kod;
        $this->dodatkowe_lokalizacje = $dodatkowe_lokalizacje;
        $this->sposob_kontaktu = $sposob_kontaktu;
    }
    
    public static function databaseToModel(array $data): static {
        return new static(
            (int)$data[self::FIELD_ID],
            $data[self::FIELD_NAZWA],
            $data[self::FIELD_FORMA_PRAWNA],
            $data[self::FIELD_NR_KRS],
            $data[self::FIELD_NR_CEIDG],
            $data[self::FIELD_NR_NIP],
            $data[self::FIELD_NR_REGON],
            $data[self::FIELD_TELEFON],
            $data[self::FIELD_EMAIL],
            $data[self::FIELD_FAX],
            $data[self::FIELD_STRONA_WWW],
            $data[self::FIELD_SIEDZ_WOJEWODZTWO],
            $data[self::FIELD_SIEDZ_POWIAT],
            $data[self::FIELD_SIEDZ_GMINA],
            $data[self::FIELD_SIEDZ_MIEJSCOWOSC],
            $data[self::FIELD_SIEDZ_ULICA],
            $data[self::FIELD_SIEDZ_NR],
            $data[self::FIELD_SIEDZ_LOKAL],
            $data[self::FIELD_SIEDZ_KOD],
            $data[self::FIELD_SPRZED_WOJEWODZTWO],
            $data[self::FIELD_SPRZED_POWIAT],
            $data[self::FIELD_SPRZED_GMINA],
            $data[self::FIELD_SPRZED_MIEJSCOWOSC],
            $data[self::FIELD_SPRZED_ULICA],
            $data[self::FIELD_SPRZED_NR],
            $data[self::FIELD_SPRZED_LOKAL],
            $data[self::FIELD_SPRZED_KOD],
            $data[self::FIELD_DODATKOWE_LOKALIZACJE],
            $data[self::FIELD_SPOSOB_KONTAKTU]
        );
    }
    
    public function modelToDatabase(): array {
        return [
            self::FIELD_NAZWA => $this->nazwa,
            self::FIELD_FORMA_PRAWNA => $this->forma_prawna,
            self::FIELD_NR_KRS => $this->nr_krs,
            self::FIELD_NR_CEIDG => $this->nr_ceidg,
            self::FIELD_NR_NIP => $this->nr_nip,
            self::FIELD_NR_REGON => $this->nr_regon,
            self::FIELD_TELEFON => $this->telefon,
            self::FIELD_EMAIL => $this->email,
            self::FIELD_FAX => $this->fax,
            self::FIELD_STRONA_WWW => $this->strona_www,
            self::FIELD_SIEDZ_WOJEWODZTWO => $this->siedz_wojewodztwo,
            self::FIELD_SIEDZ_POWIAT => $this->siedz_powiat,
            self::FIELD_SIEDZ_GMINA => $this->siedz_gmina,
            self::FIELD_SIEDZ_MIEJSCOWOSC => $this->siedz_miejscowosc,
            self::FIELD_SIEDZ_ULICA => $this->siedz_ulica,
            self::FIELD_SIEDZ_NR => $this->siedz_nr,
            self::FIELD_SIEDZ_LOKAL => $this->siedz_lokal,
            self::FIELD_SIEDZ_KOD => $this->siedz_kod,
            self::FIELD_SPRZED_WOJEWODZTWO => $this->sprzed_wojewodztwo,
            self::FIELD_SPRZED_POWIAT => $this->sprzed_powiat,
            self::FIELD_SPRZED_GMINA => $this->sprzed_gmina,
            self::FIELD_SPRZED_MIEJSCOWOSC => $this->sprzed_miejscowosc,
            self::FIELD_SPRZED_ULICA => $this->sprzed_ulica,
            self::FIELD_SPRZED_NR => $this->sprzed_nr,
            self::FIELD_SPRZED_LOKAL => $this->sprzed_lokal,
            self::FIELD_SPRZED_KOD => $this->sprzed_kod,
            self::FIELD_DODATKOWE_LOKALIZACJE => $this->dodatkowe_lokalizacje,
            self::FIELD_SPOSOB_KONTAKTU => $this->sposob_kontaktu
        ];
    }
}