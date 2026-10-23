<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProductSearchService
{
    /**
     * Stop words to filter out when tokenizing multi-word queries.
     * Note: High-value terms like 'men', 'women', 'kids' are preserved.
     */
    protected const STOP_WORDS = [
        'for', 'fro', 'the', 'in', 'and', 'with', 'of', 'to', 'a', 'an',
        'at', 'by', 'on', 'all', 'any', 'is', 'it', 'or', 'from'
    ];

    /**
     * Common plural to singular stem mappings for Barong and apparel domain.
     */
    protected const STEM_MAPPINGS = [
        'barongs'      => 'barong',
        'polos'        => 'polo',
        'dresses'      => 'dress',
        'filipinianas' => 'filipiniana',
        'mestizas'     => 'mestiza',
        'embroideries' => 'embroidery',
        'shirts'       => 'shirt',
        'coats'        => 'coat',
        'collars'      => 'collar',
        'jackets'      => 'jacket',
        'fabrics'      => 'fabric',
        'organzas'     => 'organza',
        'patterns'     => 'pattern',
        'mens'         => 'men',
        'womens'       => 'women',
        'ladies'       => 'women',
        'lady'         => 'women',
        'males'        => 'men',
        'females'      => 'women',
        'man'          => 'men',
        'woman'        => 'women',
        'gentleman'    => 'men',
        'gentlemen'    => 'men',
        'kids'         => 'kid',
        'children'     => 'kid',
        'childrens'    => 'kid',
        'child'        => 'kid',
        'boys'         => 'boy',
        'girls'        => 'girl',
    ];

    /**
     * Bounded canonical terms representing LumBarong domain vocabulary.
     */
    protected const CANONICAL_TERMS = [
        'barong', 'polo', 'jusi', 'jusilyn', 'organza', 'filipiniana', 'mestiza',
        'bolero', 'terno', 'calado', 'embroidery', 'embroidered', 'lumban',
        'monochrome', 'mandarin', 'cocoon', 'linen', 'cotton', 'silk',
        'dress', 'gown', 'blouse', 'suit', 'coat', 'jacket', 'tagalog',
        'white', 'natural', 'cream', 'pink', 'blue', 'green', 'black', 'maroon', 'gold', 'sage', 'emerald'
    ];

    /**
     * Color search synonyms allowing shoppers to find traditional white/cream barongs and colored garments.
     */
    protected const COLOR_SEARCH_SYNONYMS = [
        'white'      => ['white', 'natural', 'cream', 'ecru', 'off-white'],
        'natural'    => ['natural', 'white', 'cream', 'ecru', 'off-white'],
        'cream'      => ['cream', 'white', 'natural', 'off-white'],
        'pink'       => ['pink', 'blush', 'rose', 'magenta', 'salmon'],
        'maroon'     => ['maroon', 'burgundy', 'wine', 'pink', 'magenta', 'red'],
        'red'        => ['red', 'crimson', 'maroon', 'scarlet'],
        'blue'       => ['blue', 'navy', 'royal', 'sky', 'dusty'],
        'navy'       => ['navy', 'blue', 'midnight'],
        'green'      => ['green', 'sage', 'emerald', 'olive', 'mint'],
        'sage'       => ['sage', 'green', 'mint'],
        'emerald'    => ['emerald', 'green'],
        'black'      => ['black', 'charcoal', 'dark'],
        'gold'       => ['gold', 'golden', 'yellow'],
        'yellow'     => ['yellow', 'gold'],
        'purple'     => ['purple', 'violet', 'lavender', 'lilac'],
    ];

    /**
     * Curated typo dictionary for common marketplace search misspellings.
     */
    protected const TYPO_DICTIONARY = [
        // Barong typos
        'barng'       => 'barong',
        'brong'       => 'barong',
        'baron'       => 'barong',
        'borang'      => 'barong',
        'barongg'     => 'barong',
        'varong'      => 'barong',
        // Polo typos
        'poloo'       => 'polo',
        'ploo'        => 'polo',
        'pulo'        => 'polo',
        // Fabric typos (jusi, jusilyn, organza, pina, cocoon, linen)
        'jusli'       => 'jusi',
        'jusly'       => 'jusi',
        'jussi'       => 'jusi',
        'jusee'       => 'jusi',
        'juzi'        => 'jusi',
        'juslin'      => 'jusilyn',
        'juslyin'     => 'jusilyn',
        'jussilyn'    => 'jusilyn',
        'organz'      => 'organza',
        'orgnza'      => 'organza',
        'organzza'    => 'organza',
        'organzah'    => 'organza',
        'pnya'        => 'pina',
        'pnyaa'       => 'pina',
        'pinna'       => 'pina',
        'cocun'       => 'cocoon',
        'kocoon'      => 'cocoon',
        'linnen'      => 'linen',
        'coton'       => 'cotton',
        'cotten'      => 'cotton',
        'silkk'       => 'silk',
        // Style and garment typos
        'filipina'    => 'filipiniana',
        'filipinana'  => 'filipiniana',
        'flipiniana'  => 'filipiniana',
        'filipinyana' => 'filipiniana',
        'philipiniana'=> 'filipiniana',
        'mestisa'     => 'mestiza',
        'mestizaa'    => 'mestiza',
        'bolro'       => 'bolero',
        'bleror'      => 'bolero',
        'bollero'     => 'bolero',
        'ternoo'      => 'terno',
        'treno'       => 'terno',
        'dres'        => 'dress',
        'dresse'      => 'dress',
        'gowne'       => 'gown',
        'gwon'        => 'gown',
        'blowse'      => 'blouse',
        'suitt'       => 'suit',
        'coatt'       => 'coat',
        // Craftsmanship & Collar typos
        'kalado'      => 'calado',
        'callado'     => 'calado',
        'caldo'       => 'calado',
        'embroidry'   => 'embroidery',
        'embroydery'  => 'embroidery',
        'embroideri'  => 'embroidery',
        'embroidred'  => 'embroidered',
        'embroiderd'  => 'embroidered',
        'lumbang'     => 'lumban',
        'lumbann'     => 'lumban',
        'monocrome'   => 'monochrome',
        'monochome'   => 'monochrome',
        'manderin'    => 'mandarin',
        'chinesse'    => 'chinese',
        'artezan'     => 'artisan',
        // Demographic typos
        'mne'         => 'men',
        'wmen'        => 'women',
        'womn'        => 'women',
        'kds'         => 'kids',
        'kidds'       => 'kids',
        'byo'         => 'boy',
        'byos'        => 'boys',
        'gril'        => 'girl',
        'grils'       => 'girls',
    ];

    /**
     * Relevant generic apparel terms and their domain expansions.
     */
    protected const APPAREL_SYNONYMS = [
        'clothing'    => ['formal', 'barong', 'filipiniana', 'attire', 'wear'],
        'clothes'     => ['formal', 'barong', 'filipiniana', 'attire', 'wear'],
        'apparel'     => ['formal', 'barong', 'filipiniana', 'attire', 'wear'],
        'attire'      => ['formal', 'barong', 'filipiniana', 'wear'],
        'wear'        => ['formal', 'barong', 'filipiniana', 'attire'],
        'garment'     => ['formal', 'barong', 'filipiniana', 'attire'],
        'garments'    => ['formal', 'barong', 'filipiniana', 'attire'],
        'outfit'      => ['formal', 'barong', 'filipiniana', 'attire'],
        'outfits'     => ['formal', 'barong', 'filipiniana', 'attire'],
        'filipino'    => ['filipino', 'filipiniana', 'barong', 'tagalog', 'lumban'],
        'philippine'  => ['filipino', 'filipiniana', 'barong', 'tagalog', 'lumban'],
        'pinoy'       => ['filipino', 'filipiniana', 'barong', 'tagalog'],
        'traditional' => ['traditional', 'classic', 'tagalog', 'barong', 'filipiniana'],
        'national'    => ['national', 'barong', 'filipiniana', 'tagalog'],
    ];

    /**
     * Resolve typos against bounded dictionary and domain vocabulary.
     * Only attempts correction when token does not already match a valid word.
     */
    public static function resolveTypo(string $word): ?string
    {
        // If word is already a known stem, canonical term, or short token, do not modify
        if (in_array($word, self::CANONICAL_TERMS, true) || isset(self::STEM_MAPPINGS[$word]) || mb_strlen($word) < 4) {
            return null;
        }

        // 1. Direct match in curated typo dictionary
        if (isset(self::TYPO_DICTIONARY[$word])) {
            return self::TYPO_DICTIONARY[$word];
        }

        // 2. Bounded Levenshtein fuzzy match against canonical vocabulary
        $bestMatch = null;
        $minDist = 999;
        $wordLen = mb_strlen($word);

        foreach (self::CANONICAL_TERMS as $canonical) {
            $dist = levenshtein($word, $canonical);
            $maxAllowedDist = ($wordLen >= 7) ? 2 : 1;

            if ($dist <= $maxAllowedDist && $dist < $minDist) {
                $minDist = $dist;
                $bestMatch = $canonical;
            } elseif ($dist === $minDist && $bestMatch !== null) {
                // Ambiguity between multiple candidates: avoid guessing incorrectly
                $bestMatch = null;
            }
        }

        return $bestMatch;
    }

    /**
     * Normalize raw search string:
     * - Handle possessives ('s, ’s)
     * - Normalize accents (ñ -> n / pina -> piña compatibility)
     * - Strip unnecessary punctuation
     */
    public static function normalize(string $query): string
    {
        $clean = mb_strtolower(trim($query), 'UTF-8');
        // Normalize possessive 's (men's -> men, women's -> women, kid's -> kid, boy's -> boy, girl's -> girl)
        $clean = preg_replace("/['’]s\b/u", '', $clean);
        // Normalize split syllables / accidental spaces in domain keywords (e.g. 'organ z' / 'organ za' -> 'organza')
        $clean = preg_replace('/\borgan\s+z(?:a)?\b/u', 'organza', $clean);
        $clean = preg_replace('/\bbar\s+ong\b/u', 'barong', $clean);
        $clean = preg_replace('/\bpo\s+lo\b/u', 'polo', $clean);
        $clean = preg_replace('/\bju\s+si\b/u', 'jusi', $clean);
        // Replace non-alphanumeric (except accented letters) with spaces
        $clean = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $clean);
        return trim((string) preg_replace('/\s+/', ' ', $clean));
    }

    /**
     * Detect intended target audience from search string.
     * Returns: 'Men', 'Women', 'Boys', 'Girls', 'Kids', or null.
     */
    public static function detectTargetAudience(string $normalized): ?string
    {
        $words = preg_split('/\s+/', $normalized);
        foreach ($words as $w) {
            if (in_array($w, ['boy', 'boys', 'byo', 'byos'], true)) {
                return 'Boys';
            }
            if (in_array($w, ['girl', 'girls', 'gril', 'grils'], true)) {
                return 'Girls';
            }
            if (in_array($w, ['kid', 'kids', 'child', 'children', 'kds', 'kidds'], true)) {
                return 'Kids';
            }
            if (in_array($w, ['men', 'male', 'man', 'gentleman', 'gentlemen', 'mne'], true)) {
                return 'Men';
            }
            if (in_array($w, ['women', 'woman', 'female', 'ladies', 'lady', 'filipiniana', 'mestiza', 'terno', 'wmen', 'womn'], true)) {
                return 'Women';
            }
        }
        return null;
    }

    /**
     * Extract clean searchable tokens, stripping stop words, resolving typos, and stemming plurals.
     * Returns array of token variants (singular, plural, typos, and synonyms where applicable).
     *
     * @return array<int, array{original: string, resolved: string, variants: array<string>}>
     */
    public static function extractTokens(string $normalized, ?string $detectedAudience = null): array
    {
        $words = preg_split('/\s+/', $normalized);
        $tokens = [];

        foreach ($words as $w) {
            $w = trim($w);
            if (empty($w)) {
                continue;
            }

            // Skip common stop words if we have more than one word
            if (count($words) > 1 && in_array($w, self::STOP_WORDS, true)) {
                continue;
            }

            $variants = [$w];

            // 1. Check Typo Correction (if token is misspelled)
            $resolvedTypo = self::resolveTypo($w);
            if ($resolvedTypo && !in_array($resolvedTypo, $variants, true)) {
                $variants[] = $resolvedTypo;
                $effectiveWord = $resolvedTypo;
            } else {
                $effectiveWord = $w;
            }

            // 2. Stem mapping
            if (isset(self::STEM_MAPPINGS[$effectiveWord])) {
                $stemmed = self::STEM_MAPPINGS[$effectiveWord];
                if (!in_array($stemmed, $variants, true)) {
                    $variants[] = $stemmed;
                }
            } elseif (str_ends_with($effectiveWord, 's') && strlen($effectiveWord) > 3) {
                $singular = substr($effectiveWord, 0, -1);
                if (!in_array($singular, $variants, true)) {
                    $variants[] = $singular;
                }
            } else {
                // Plural variant
                $plural = $effectiveWord . 's';
                if (!in_array($plural, $variants, true)) {
                    $variants[] = $plural;
                }
            }

            // 3. Jusi / Jusilyn bidirectional relationship
            if ($effectiveWord === 'jusi' || in_array('jusi', $variants, true)) {
                if (!in_array('jusilyn', $variants, true)) {
                    $variants[] = 'jusilyn';
                }
            } elseif ($effectiveWord === 'jusilyn' || in_array('jusilyn', $variants, true)) {
                if (!in_array('jusi', $variants, true)) {
                    $variants[] = 'jusi';
                }
            }

            // 4. Piña / Pina accent variant
            if (str_contains($effectiveWord, 'pina') || str_contains($effectiveWord, 'piña')) {
                if (!in_array('piña', $variants, true)) $variants[] = 'piña';
                if (!in_array('pina', $variants, true)) $variants[] = 'pina';
            }

            // 5. Generic apparel & culture synonyms respecting target audience
            if (isset(self::APPAREL_SYNONYMS[$effectiveWord])) {
                $synonyms = self::APPAREL_SYNONYMS[$effectiveWord];
                
                // Audience contextual filtering for generic apparel terms
                if (in_array($effectiveWord, ['clothing', 'clothes', 'apparel', 'attire', 'wear', 'garment', 'garments', 'outfit', 'outfits'], true)) {
                    if (in_array($detectedAudience, ['Men', 'Boys'], true)) {
                        // For Men/Boys: expand to formalwear/barong, exclude women's dresses
                        $synonyms = array_diff($synonyms, ['filipiniana', 'dress', 'gown', 'bolero', 'mestiza']);
                    } elseif (in_array($detectedAudience, ['Women', 'Girls'], true)) {
                        // For Women/Girls: expand to filipiniana/dress/gown/formal, exclude men's pure barongs
                        $synonyms = array_diff($synonyms, ['polo']);
                        if (!in_array('dress', $synonyms, true)) {
                            $synonyms[] = 'dress';
                            $synonyms[] = 'gown';
                        }
                    }
                }

                foreach ($synonyms as $syn) {
                    if (!in_array($syn, $variants, true)) {
                        $variants[] = $syn;
                    }
                }
            }

            // 6. Color search synonyms (e.g. white matches natural/cream, pink matches blush/magenta)
            if (isset(self::COLOR_SEARCH_SYNONYMS[$effectiveWord])) {
                foreach (self::COLOR_SEARCH_SYNONYMS[$effectiveWord] as $colorSyn) {
                    if (!in_array($colorSyn, $variants, true)) {
                        $variants[] = $colorSyn;
                    }
                }
            }

            $tokens[] = [
                'original' => $w,
                'resolved' => $effectiveWord,
                'variants' => array_values(array_unique($variants))
            ];
        }

        return $tokens;
    }

    /**
     * Apply flexible, attribute-combining search and relevance ranking to Eloquent Product Query.
     */
    public static function apply(Builder $query, ?string $rawSearch, ?string $sort = null): Builder
    {
        if (empty($rawSearch) || trim($rawSearch) === '') {
            return $query;
        }

        $rawSearch = trim((string)$rawSearch);
        $normalized = self::normalize($rawSearch);

        // For very short 1-2 char inputs, match name strictly to avoid random noisy matches
        if (mb_strlen($rawSearch, 'UTF-8') <= 2) {
            return $query->where('name', 'like', '%' . $rawSearch . '%');
        }

        $detectedAudience = self::detectTargetAudience($normalized);
        $tokens = self::extractTokens($normalized, $detectedAudience);

        if (empty($tokens)) {
            return $query->where('name', 'like', '%' . $rawSearch . '%');
        }

        $demographicTerms = [
            'men', 'mens', 'male', 'males', 'man', 'gentleman', 'gentlemen', 'mne',
            'women', 'womens', 'woman', 'female', 'females', 'ladies', 'lady', 'filipiniana', 'filipinianas', 'mestiza', 'mestizas', 'terno', 'ternos', 'wmen', 'womn',
            'kid', 'kids', 'child', 'children', 'childrens', 'kds', 'kidds', 'boy', 'boys', 'byo', 'byos', 'girl', 'girls', 'gril', 'grils'
        ];

        $attributeTokens = array_values(array_filter($tokens, function ($t) use ($demographicTerms) {
            $orig = strtolower($t['original'] ?? '');
            $resolved = strtolower($t['resolved'] ?? $orig);
            return !in_array($orig, $demographicTerms, true) && !in_array($resolved, $demographicTerms, true);
        }));

        $isPureDemographicSearch = ($detectedAudience !== null) && empty($attributeTokens);

        // Case A: Pure demographic search (e.g. 'men', 'male', 'women', 'kids', 'filipiniana')
        // Directly isolate the demographic collection and eliminate substring collisions like 'women' or 'garment'
        if ($isPureDemographicSearch) {
            if ($detectedAudience === 'Men') {
                $query->where(function (Builder $q) {
                    $q->where(function ($sq) {
                        $sq->where('target_group', 'like', '%men%')
                           ->where('target_group', 'not like', '%women%');
                    })->orWhere(function ($sq) {
                        $sq->whereNull('target_group')
                           ->where('name', 'like', '%barong%')
                           ->where('name', 'not like', '%lady%');
                    })->orWhereHas('category', function ($cq) {
                        $cq->where('name', 'like', '%men%')
                           ->where('name', 'not like', '%women%');
                    });
                })
                ->where(function (Builder $q) {
                    $q->where('target_group', 'not like', '%women%')
                      ->where('target_group', 'not like', '%female%')
                      ->where('target_group', 'not like', '%kid%')
                      ->orWhereNull('target_group');
                })
                ->where('name', 'not like', '%filipiniana%')
                ->where('name', 'not like', '%bolero%')
                ->where('name', 'not like', '%mestiza%')
                ->where('name', 'not like', '%terno%')
                ->where('name', 'not like', '%gown%')
                ->where('name', 'not like', '%dress%')
                ->where('name', 'not like', '%women%');
            } elseif ($detectedAudience === 'Women') {
                $query->where(function (Builder $q) {
                    $q->where('target_group', 'like', '%women%')
                      ->orWhere('target_group', 'like', '%female%')
                      ->orWhere('name', 'like', '%filipiniana%')
                      ->orWhere('name', 'like', '%bolero%')
                      ->orWhere('name', 'like', '%mestiza%')
                      ->orWhere('name', 'like', '%terno%')
                      ->orWhere('name', 'like', '%gown%')
                      ->orWhere('name', 'like', '%dress%')
                      ->orWhere('name', 'like', '%lady%')
                      ->orWhereHas('category', function ($cq) {
                          $cq->where('name', 'like', '%women%')
                             ->orWhere('name', 'like', '%filipiniana%')
                             ->orWhere('name', 'like', '%gown%');
                      });
                })
                ->where(function (Builder $q) {
                    $q->where(function ($sq) {
                        $sq->where('target_group', 'not like', '%men%')
                           ->orWhere('target_group', 'like', '%women%');
                    })->orWhereNull('target_group');
                });
            } elseif ($detectedAudience === 'Boys') {
                $query->where(function (Builder $q) {
                    $q->where(function ($sq) {
                        $sq->where('target_group', 'like', '%boy%')
                           ->orWhere('target_group', 'like', '%kid%')
                           ->orWhere('target_group', 'like', '%child%');
                    })->orWhere('name', 'like', '%boy%')
                      ->orWhereHas('category', function ($cq) {
                          $cq->where('name', 'like', '%kid%')
                             ->orWhere('name', 'like', '%boy%');
                      });
                })
                ->where(function (Builder $q) {
                    $q->where('target_group', 'not like', '%women%')
                      ->where('target_group', 'not like', '%girl%')
                      ->orWhereNull('target_group');
                })
                ->where('name', 'not like', '%filipiniana%')
                ->where('name', 'not like', '%gown%')
                ->where('name', 'not like', '%dress%')
                ->where('name', 'not like', '%girl%');
            } elseif ($detectedAudience === 'Girls') {
                $query->where(function (Builder $q) {
                    $q->where(function ($sq) {
                        $sq->where('target_group', 'like', '%girl%')
                           ->orWhere('target_group', 'like', '%kid%')
                           ->orWhere('target_group', 'like', '%child%');
                    })->orWhere('name', 'like', '%girl%')
                      ->orWhereHas('category', function ($cq) {
                          $cq->where('name', 'like', '%kid%')
                             ->orWhere('name', 'like', '%girl%');
                      });
                })
                ->where(function (Builder $q) {
                    $q->whereNotIn('target_group', ['Men', 'Boys', 'boy', 'male'])
                      ->where('target_group', 'not like', '%boy%')
                      ->orWhere('name', 'like', '%lady%')
                      ->orWhere('name', 'like', '%filipiniana%')
                      ->orWhere('name', 'like', '%dress%')
                      ->orWhereNull('target_group');
                })
                ->where('name', 'not like', '%boy%');
            } else {
                // Kids general
                $query->where(function (Builder $q) {
                    $q->where('target_group', 'like', '%kid%')
                      ->orWhere('target_group', 'like', '%child%')
                      ->orWhere('name', 'like', '%kid%')
                      ->orWhere('name', 'like', '%boy%')
                      ->orWhere('name', 'like', '%girl%')
                      ->orWhereHas('category', function ($cq) {
                          $cq->where('name', 'like', '%kid%')
                             ->orWhere('name', 'like', '%child%');
                      });
                });
            }

            self::applyRelevanceOrder($query, $rawSearch, $normalized, $tokens, $detectedAudience, $sort);
            return $query;
        }

        // Case B: Search query containing demographic modifier and/or specific garment attributes
        // Enforce demographic exclusion boundaries first
        if ($detectedAudience === 'Men') {
            $query->where(function (Builder $q) {
                $q->where('target_group', 'not like', '%women%')
                  ->where('target_group', 'not like', '%female%')
                  ->orWhereNull('target_group');
            })
            ->where('name', 'not like', '%filipiniana%')
            ->where('name', 'not like', '%bolero%')
            ->where('name', 'not like', '%mestiza%')
            ->where('name', 'not like', '%terno%');
        } elseif ($detectedAudience === 'Women') {
            $query->where(function (Builder $q) {
                $q->where(function ($sq) {
                    $sq->where('target_group', 'not like', '%men%')
                       ->orWhere('target_group', 'like', '%women%')
                       ->orWhere('name', 'like', '%lady%')
                       ->orWhere('name', 'like', '%filipiniana%')
                       ->orWhere('name', 'like', '%bolero%')
                       ->orWhere('name', 'like', '%mestiza%')
                       ->orWhere('name', 'like', '%terno%');
                })->orWhereNull('target_group');
            });
        } elseif ($detectedAudience === 'Boys') {
            $query->where(function (Builder $q) {
                $q->where('target_group', 'not like', '%women%')
                  ->where('target_group', 'not like', '%girl%')
                  ->orWhereNull('target_group');
            })
            ->where('name', 'not like', '%filipiniana%')
            ->where('name', 'not like', '%gown%')
            ->where('name', 'not like', '%dress%')
            ->where('name', 'not like', '%girl%');
        } elseif ($detectedAudience === 'Girls') {
            $query->where(function (Builder $q) {
                $q->where(function ($sq) {
                    $sq->whereNotIn('target_group', ['Men', 'Boys', 'boy', 'male'])
                       ->where('target_group', 'not like', '%boy%')
                       ->orWhere('name', 'like', '%lady%')
                       ->orWhere('name', 'like', '%filipiniana%')
                       ->orWhere('name', 'like', '%dress%');
                })->orWhereNull('target_group');
            })
            ->where('name', 'not like', '%boy%');
        } elseif ($detectedAudience === 'Kids') {
            $query->where(function (Builder $q) {
                $q->where('target_group', 'like', '%kid%')
                  ->orWhere('target_group', 'like', '%child%')
                  ->orWhere('name', 'like', '%kid%')
                  ->orWhere('name', 'like', '%boy%')
                  ->orWhere('name', 'like', '%girl%')
                  ->orWhereHas('category', function ($cq) {
                      $cq->where('name', 'like', '%kid%')
                         ->orWhere('name', 'like', '%child%');
                  });
            });
        }

        $searchTokens = !empty($attributeTokens) ? $attributeTokens : $tokens;

        // Apply WHERE clauses combining keywords across product attributes
        $query->where(function (Builder $mainQuery) use ($rawSearch, $normalized, $searchTokens, $detectedAudience) {
            // Path 1: Direct full phrase match on attributes (when attributes exist)
            $mainQuery->where(function (Builder $pq) use ($rawSearch, $normalized) {
                $pq->where('name', 'like', '%' . $rawSearch . '%')
                   ->orWhere('name', 'like', '%' . $normalized . '%')
                   ->orWhere('description', 'like', '%' . $rawSearch . '%')
                   ->orWhere('description', 'like', '%' . $normalized . '%')
                   ->orWhere('color', 'like', '%' . $rawSearch . '%')
                   ->orWhere('color', 'like', '%' . $normalized . '%')
                   ->orWhere('colors', 'like', '%' . $rawSearch . '%')
                   ->orWhere('colors', 'like', '%' . $normalized . '%')
                   ->orWhere('sku', 'like', '%' . $rawSearch . '%')
                   ->orWhereHas('category', function ($cq) use ($rawSearch, $normalized) {
                       $cq->where('name', 'like', '%' . $rawSearch . '%')
                          ->orWhere('name', 'like', '%' . $normalized . '%');
                   });
            });

            // Path 2: Token-based multi-attribute match
            // Each meaningful token must match AT LEAST ONE searchable field
            $mainQuery->orWhere(function (Builder $tokenQuery) use ($searchTokens, $detectedAudience) {
                $isFemaleSearch = in_array($detectedAudience, ['Women', 'Girls'], true);

                foreach ($searchTokens as $tokenData) {
                    $orig = $tokenData['original'];
                    $resolved = $tokenData['resolved'] ?? $orig;
                    $variants = $tokenData['variants'];

                    // General attribute matching
                    $tokenQuery->where(function (Builder $subQuery) use ($variants, $orig, $resolved, $isFemaleSearch) {
                        foreach ($variants as $v) {
                            $subQuery->orWhere('name', 'like', '%' . $v . '%')
                                     ->orWhere('description', 'like', '%' . $v . '%')
                                     ->orWhere('color', 'like', '%' . $v . '%')
                                     ->orWhere('colors', 'like', '%' . $v . '%')
                                     ->orWhere('fabric_type', 'like', '%' . $v . '%')
                                     ->orWhere('collar_type', 'like', '%' . $v . '%')
                                     ->orWhere('artisan_region', 'like', '%' . $v . '%')
                                     ->orWhere('sku', 'like', '%' . $v . '%')
                                     ->orWhere('categories', 'like', '%' . $v . '%')
                                     ->orWhere('variations', 'like', '%' . $v . '%')
                                     ->orWhereHas('category', function ($cq) use ($v) {
                                         $cq->where('name', 'like', '%' . $v . '%');
                                     })
                                     ->orWhereHas('seller', function ($sq) use ($v) {
                                         $sq->where('shopName', 'like', '%' . $v . '%')
                                            ->orWhere('name', 'like', '%' . $v . '%');
                                     });

                            // If 'barong' is searched in female context (e.g. 'barong for girl' or 'barong for women'),
                            // also match Filipiniana / Lady Barong attire
                            if (($v === 'barong' || $resolved === 'barong') && $isFemaleSearch) {
                                $subQuery->orWhere('name', 'like', '%filipiniana%')
                                         ->orWhere('name', 'like', '%lady%')
                                         ->orWhere('name', 'like', '%bolero%')
                                         ->orWhere('name', 'like', '%mestiza%')
                                         ->orWhereHas('category', function ($cq) {
                                             $cq->where('name', 'like', '%filipiniana%')
                                                ->orWhere('name', 'like', '%lady%')
                                                ->orWhere('name', 'like', '%gown%');
                                         });
                            }
                        }
                    });
                }
            });
        });

        // Apply Relevance Ranking when no explicit sort (like price, newest, or rating) is requested
        self::applyRelevanceOrder($query, $rawSearch, $normalized, $tokens, $detectedAudience, $sort);

        return $query;
    }

    /**
     * Compute and apply relevance score ordering.
     */
    protected static function applyRelevanceOrder(
        Builder $query,
        string $rawSearch,
        string $normalized,
        array $tokens,
        ?string $detectedAudience,
        ?string $sort
    ): void {
        // If customer explicitly requested a standard sort (e.g. Price Low->High, Newest, or Rated), honor that order first
        if ($sort && in_array($sort, ['price_asc', 'price_desc', 'newest', 'price_low', 'price_high', 'cheapest', 'expensive', 'rated', 'highest_rated'])) {
            return;
        }

        $relevanceCases = [];
        $bindings = [];

        // 1. Exact full name match (+250 points)
        $relevanceCases[] = "CASE WHEN LOWER(products.name) = ? THEN 250 ELSE 0 END";
        $bindings[] = $normalized;

        // 2. Full phrase in name (+150 points)
        $relevanceCases[] = "CASE WHEN LOWER(products.name) LIKE ? THEN 150 ELSE 0 END";
        $bindings[] = '%' . $normalized . '%';

        // 3. Demographic & Target Audience Alignment (+300 for Kids top boost, -300 for gender conflict)
        if ($detectedAudience === 'Boys') {
            $relevanceCases[] = "CASE 
                WHEN (LOWER(products.target_group) = 'kids' OR LOWER(products.target_group) LIKE '%kid%') THEN 300 
                WHEN LOWER(products.name) LIKE '%boy%' THEN 150
                WHEN (LOWER(products.target_group) = 'men' OR (LOWER(products.target_group) LIKE '%men%' AND LOWER(products.target_group) NOT LIKE '%women%')) THEN 50
                WHEN (LOWER(products.target_group) = 'women' OR LOWER(products.target_group) LIKE '%women%') THEN -300
                ELSE 0 END";
        } elseif ($detectedAudience === 'Girls') {
            $relevanceCases[] = "CASE 
                WHEN (LOWER(products.target_group) = 'kids' OR LOWER(products.target_group) LIKE '%kid%') THEN 300 
                WHEN LOWER(products.name) LIKE '%girl%' THEN 150
                WHEN (LOWER(products.target_group) = 'women' OR LOWER(products.target_group) LIKE '%women%' OR LOWER(products.target_group) LIKE '%female%') THEN 80 
                WHEN (LOWER(products.target_group) = 'men' AND LOWER(products.target_group) NOT LIKE '%women%') THEN -300
                ELSE 0 END";
        } elseif ($detectedAudience === 'Kids') {
            $relevanceCases[] = "CASE 
                WHEN (LOWER(products.target_group) = 'kids' OR LOWER(products.target_group) LIKE '%kid%' OR LOWER(products.target_group) LIKE '%child%') THEN 300 
                WHEN (LOWER(products.name) LIKE '%kid%' OR LOWER(products.description) LIKE '%kid%') THEN 150
                ELSE 0 END";
        } elseif ($detectedAudience === 'Men') {
            $relevanceCases[] = "CASE 
                WHEN (LOWER(products.target_group) = 'men' OR (LOWER(products.target_group) LIKE '%men%' AND LOWER(products.target_group) NOT LIKE '%women%')) THEN 100 
                WHEN (LOWER(products.target_group) = 'women' OR LOWER(products.target_group) LIKE '%women%') THEN -150
                ELSE 0 END";
        } elseif ($detectedAudience === 'Women') {
            $relevanceCases[] = "CASE 
                WHEN (LOWER(products.target_group) = 'women' OR LOWER(products.target_group) LIKE '%women%' OR LOWER(products.target_group) LIKE '%female%') THEN 100 
                WHEN (LOWER(products.target_group) = 'men' AND LOWER(products.target_group) NOT LIKE '%women%') THEN -150
                ELSE 0 END";
        }

        // 4. Per-token name match (+40 points per token) and fabric match (+30 points)
        foreach ($tokens as $tokenData) {
            $orig = $tokenData['original'];
            $resolved = $tokenData['resolved'] ?? $orig;

            if (in_array($resolved, ['men', 'women', 'kids', 'boy', 'boys', 'girl', 'girls', 'for', 'the'], true)) {
                continue;
            }

            // Name match (checks original token and resolved typo/canonical word)
            $nameChecks = ["LOWER(products.name) LIKE ?"];
            $bindings[] = '%' . $orig . '%';
            if ($resolved !== $orig) {
                $nameChecks[] = "LOWER(products.name) LIKE ?";
                $bindings[] = '%' . $resolved . '%';
            }
            $relevanceCases[] = "CASE WHEN (" . implode(' OR ', $nameChecks) . ") THEN 40 ELSE 0 END";

            // Fabric type match (+30 points)
            $fabricChecks = ["LOWER(COALESCE(products.fabric_type, '')) LIKE ?"];
            $bindings[] = '%' . $orig . '%';
            if ($resolved !== $orig) {
                $fabricChecks[] = "LOWER(COALESCE(products.fabric_type, '')) LIKE ?";
                $bindings[] = '%' . $resolved . '%';
            }
            if ($resolved === 'jusi') {
                $fabricChecks[] = "LOWER(COALESCE(products.fabric_type, '')) LIKE ?";
                $bindings[] = '%jusilyn%';
            }
            $relevanceCases[] = "CASE WHEN (" . implode(' OR ', $fabricChecks) . ") THEN 30 ELSE 0 END";

            // Color match (+50 points)
            $colorChecks = [
                "LOWER(COALESCE(products.color, '')) LIKE ?",
                "LOWER(COALESCE(products.colors, '')) LIKE ?"
            ];
            $bindings[] = '%' . $orig . '%';
            $bindings[] = '%' . $orig . '%';
            if ($resolved !== $orig) {
                $colorChecks[] = "LOWER(COALESCE(products.color, '')) LIKE ?";
                $colorChecks[] = "LOWER(COALESCE(products.colors, '')) LIKE ?";
                $bindings[] = '%' . $resolved . '%';
                $bindings[] = '%' . $resolved . '%';
            }
            if (isset(self::COLOR_SEARCH_SYNONYMS[$resolved])) {
                foreach (self::COLOR_SEARCH_SYNONYMS[$resolved] as $cSyn) {
                    $colorChecks[] = "LOWER(COALESCE(products.color, '')) LIKE ?";
                    $colorChecks[] = "LOWER(COALESCE(products.colors, '')) LIKE ?";
                    $bindings[] = '%' . $cSyn . '%';
                    $bindings[] = '%' . $cSyn . '%';
                }
            }
            $relevanceCases[] = "CASE WHEN (" . implode(' OR ', $colorChecks) . ") THEN 50 ELSE 0 END";
        }

        if (!empty($relevanceCases)) {
            $sqlRelevance = '(' . implode(' + ', $relevanceCases) . ')';
            $query->orderByRaw($sqlRelevance . ' DESC', $bindings);
        }
    }
}
