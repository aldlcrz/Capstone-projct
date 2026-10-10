<?php

namespace App\Services\Shipping;

class LagunaMunicipalityCatalog
{
    /**
     * Authoritative list of all 30 cities and municipalities in Laguna Province, Philippines.
     */
    protected static array $municipalities = [
        'alaminos' => [
            'key'         => 'alaminos',
            'name'        => 'Alaminos',
            'postal_code' => '4001',
            'aliases'     => ['alaminos', 'alaminos laguna'],
        ],
        'bay' => [
            'key'         => 'bay',
            'name'        => 'Bay',
            'postal_code' => '4033',
            'aliases'     => ['bay', 'bay laguna'],
        ],
        'binan' => [
            'key'         => 'binan',
            'name'        => 'Biñan',
            'postal_code' => '4024',
            'aliases'     => ['binan', 'biñan', 'city of binan', 'city of biñan', 'binan city', 'biñan city'],
        ],
        'cabuyao' => [
            'key'         => 'cabuyao',
            'name'        => 'Cabuyao',
            'postal_code' => '4025',
            'aliases'     => ['cabuyao', 'city of cabuyao', 'cabuyao city'],
        ],
        'calamba' => [
            'key'         => 'calamba',
            'name'        => 'Calamba',
            'postal_code' => '4027',
            'aliases'     => ['calamba', 'city of calamba', 'calamba city'],
        ],
        'calauan' => [
            'key'         => 'calauan',
            'name'        => 'Calauan',
            'postal_code' => '4012',
            'aliases'     => ['calauan'],
        ],
        'cavinti' => [
            'key'         => 'cavinti',
            'name'        => 'Cavinti',
            'postal_code' => '4013',
            'aliases'     => ['cavinti'],
        ],
        'famy' => [
            'key'         => 'famy',
            'name'        => 'Famy',
            'postal_code' => '4020',
            'aliases'     => ['famy'],
        ],
        'kalayaan' => [
            'key'         => 'kalayaan',
            'name'        => 'Kalayaan',
            'postal_code' => '4015',
            'aliases'     => ['kalayaan'],
        ],
        'liliw' => [
            'key'         => 'liliw',
            'name'        => 'Liliw',
            'postal_code' => '4004',
            'aliases'     => ['liliw'],
        ],
        'los_banos' => [
            'key'         => 'los_banos',
            'name'        => 'Los Baños',
            'postal_code' => '4030',
            'aliases'     => ['los banos', 'los baños', 'los baños laguna', 'los banos laguna'],
        ],
        'luisiana' => [
            'key'         => 'luisiana',
            'name'        => 'Luisiana',
            'postal_code' => '4032',
            'aliases'     => ['luisiana'],
        ],
        'lumban' => [
            'key'         => 'lumban',
            'name'        => 'Lumban',
            'postal_code' => '4014',
            'aliases'     => ['lumban', 'lumban laguna'],
        ],
        'mabitac' => [
            'key'         => 'mabitac',
            'name'        => 'Mabitac',
            'postal_code' => '4021',
            'aliases'     => ['mabitac'],
        ],
        'magdalena' => [
            'key'         => 'magdalena',
            'name'        => 'Magdalena',
            'postal_code' => '4007',
            'aliases'     => ['magdalena'],
        ],
        'majayjay' => [
            'key'         => 'majayjay',
            'name'        => 'Majayjay',
            'postal_code' => '4005',
            'aliases'     => ['majayjay'],
        ],
        'nagcarlan' => [
            'key'         => 'nagcarlan',
            'name'        => 'Nagcarlan',
            'postal_code' => '4002',
            'aliases'     => ['nagcarlan'],
        ],
        'paete' => [
            'key'         => 'paete',
            'name'        => 'Paete',
            'postal_code' => '4016',
            'aliases'     => ['paete'],
        ],
        'pagsanjan' => [
            'key'         => 'pagsanjan',
            'name'        => 'Pagsanjan',
            'postal_code' => '4008',
            'aliases'     => ['pagsanjan'],
        ],
        'pakil' => [
            'key'         => 'pakil',
            'name'        => 'Pakil',
            'postal_code' => '4017',
            'aliases'     => ['pakil'],
        ],
        'pangil' => [
            'key'         => 'pangil',
            'name'        => 'Pangil',
            'postal_code' => '4018',
            'aliases'     => ['pangil'],
        ],
        'pila' => [
            'key'         => 'pila',
            'name'        => 'Pila',
            'postal_code' => '4010',
            'aliases'     => ['pila'],
        ],
        'rizal' => [
            'key'         => 'rizal',
            'name'        => 'Rizal',
            'postal_code' => '4003',
            'aliases'     => ['rizal', 'rizal laguna'],
        ],
        'san_pablo' => [
            'key'         => 'san_pablo',
            'name'        => 'San Pablo',
            'postal_code' => '4000',
            'aliases'     => ['san pablo', 'city of san pablo', 'san pablo city'],
        ],
        'san_pedro' => [
            'key'         => 'san_pedro',
            'name'        => 'San Pedro',
            'postal_code' => '4023',
            'aliases'     => ['san pedro', 'city of san pedro', 'san pedro city'],
        ],
        'santa_cruz' => [
            'key'         => 'santa_cruz',
            'name'        => 'Santa Cruz',
            'postal_code' => '4009',
            'aliases'     => ['santa cruz', 'sta. cruz', 'sta cruz', 'sta.cruz'],
        ],
        'santa_maria' => [
            'key'         => 'santa_maria',
            'name'        => 'Santa Maria',
            'postal_code' => '4022',
            'aliases'     => ['santa maria', 'sta. maria', 'sta maria', 'sta.maria'],
        ],
        'santa_rosa' => [
            'key'         => 'santa_rosa',
            'name'        => 'Santa Rosa',
            'postal_code' => '4026',
            'aliases'     => ['santa rosa', 'sta. rosa', 'sta rosa', 'sta.rosa', 'city of santa rosa', 'santa rosa city'],
        ],
        'siniloan' => [
            'key'         => 'siniloan',
            'name'        => 'Siniloan',
            'postal_code' => '4019',
            'aliases'     => ['siniloan'],
        ],
        'victoria' => [
            'key'         => 'victoria',
            'name'        => 'Victoria',
            'postal_code' => '4011',
            'aliases'     => ['victoria', 'victoria laguna'],
        ],
    ];

    /**
     * Get all 30 municipalities in standard alphabetized order.
     */
    public static function all(): array
    {
        $list = static::$municipalities;
        uasort($list, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $list;
    }

    /**
     * Get list of all canonical keys.
     */
    public static function keys(): array
    {
        return array_keys(static::$municipalities);
    }

    /**
     * Validate if a key is a supported Laguna municipality.
     */
    public static function isValidKey(?string $key): bool
    {
        if (empty($key)) return false;
        return isset(static::$municipalities[strtolower(trim($key))]);
    }

    /**
     * Get display name for a given key.
     */
    public static function getName(string $key): string
    {
        $normalized = strtolower(trim($key));
        return static::$municipalities[$normalized]['name'] ?? ucfirst($key);
    }

    /**
     * Resolve any user-inputted city/municipality name string to its canonical Laguna key.
     * Returns null if the location is outside Laguna or cannot be resolved.
     */
    public static function resolveKey(?string $cityOrMunicipality): ?string
    {
        if (empty($cityOrMunicipality)) {
            return null;
        }

        $input = strtolower(trim($cityOrMunicipality));

        // Direct key match
        if (isset(static::$municipalities[$input])) {
            return $input;
        }

        // Clean up common prefixes / suffixes
        $clean = preg_replace('/^(city of|municipality of)\s+/i', '', $input);
        $clean = preg_replace('/\s+city$/i', '', $clean);
        $clean = trim($clean);

        if (isset(static::$municipalities[$clean])) {
            return $clean;
        }

        // Search alias dictionary
        foreach (static::$municipalities as $key => $data) {
            foreach ($data['aliases'] as $alias) {
                if ($input === $alias || $clean === $alias) {
                    return $key;
                }
                // Partial word boundary check
                if (str_contains($input, $alias) || str_contains($alias, $input)) {
                    return $key;
                }
            }
        }

        return null;
    }
}
