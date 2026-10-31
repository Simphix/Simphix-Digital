<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TravelPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'tagline',
        'description',
        'price_range',
        'duration',
        'highlights',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'highlights'  => 'array',
        'is_featured' => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(TravelRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Default catalog packages if none exist.
     */
    public static function seedDefaults(): void
    {
        if (static::count() > 0) {
            return;
        }

        $packages = [
            // Umrah Packages
            [
                'name'         => 'Economy Umrah Package',
                'slug'         => 'economy-umrah-package',
                'category'     => 'umrah',
                'tagline'      => 'Affordable and spiritually fulfilling pilgrimage with verified hotel accommodation.',
                'description'  => 'Complete Umrah experience designed for budget-conscious pilgrims. Includes Umrah visa processing, round-trip economy flights, 3-star hotel accommodation in Makkah and Madinah with shuttle services, ground transfers, and ziyarat guided tours.',
                'price_range'  => 'From ₦2,650,000 / person',
                'duration'     => '14 Days / 13 Nights',
                'highlights'   => [
                    'Umrah e-Visa processing & health insurance',
                    'Round-trip scheduled airline flights',
                    '3-star standard hotel accommodation (Makkah & Madinah)',
                    'Air-conditioned airport & intercity transfers',
                    'Guided Ziyarat to historical holy sites',
                    'Dedicated SimPhix on-ground support guide',
                ],
                'is_featured'  => true,
                'is_active'    => true,
                'sort_order'   => 1,
            ],
            [
                'name'         => 'VIP & Ramadan Premium Umrah',
                'slug'         => 'vip-ramadan-premium-umrah',
                'category'     => 'umrah',
                'tagline'      => '5-star Haram-view luxury hotels, express visa processing, and premium private transfers.',
                'description'  => 'Experience spiritual serenity in utmost comfort. Features 5-star hotels right on the Haram courtyards in Makkah (Clock Tower / Swissotel) and Madinah, private luxury vehicle transfers, buffet dining, and fast-track VIP visa handling.',
                'price_range'  => 'From ₦4,850,000 / person',
                'duration'     => '10 to 15 Days',
                'highlights'   => [
                    'Express VIP Umrah visa processing',
                    '5-Star hotels directly facing the Holy Mosques',
                    'Daily open buffet breakfast & Iftar/Suhoor in Ramadan',
                    'Private VIP luxury vehicle transfers (GMC / Mercedes)',
                    'Comprehensive guided historical tours',
                    'Complimentary 5L Zamzam water container',
                ],
                'is_featured'  => true,
                'is_active'    => true,
                'sort_order'   => 2,
            ],

            // Hajj Packages
            [
                'name'         => 'Standard Hajj Pilgrimage Package',
                'slug'         => 'standard-hajj-pilgrimage',
                'category'     => 'hajj',
                'tagline'      => 'Complete, well-organized Hajj journey with dedicated scholars and organized camps.',
                'description'  => 'Fulfill your fifth pillar of Islam with peace of mind. Complete logistical management including official Hajj visa registration, tent accommodation in Mina & Arafat, hotel stays in Makkah & Madinah, air-conditioned bus transportation, and knowledgeable Islamic scholars for spiritual guidance.',
                'price_range'  => 'Official 2026/2027 NAHCON / Private quota',
                'duration'     => '21 to 28 Days',
                'highlights'   => [
                    'Official Hajj visa & pilgrim registration',
                    'Round-trip international flight tickets',
                    'Tent accommodation in Mina & Arafat with meal provision',
                    'Hotel accommodation in Makkah & Madinah',
                    'Experienced Islamic scholars & welfare doctors accompanying group',
                    'Sacrifice (Hady / Qurbani) arrangement support',
                ],
                'is_featured'  => true,
                'is_active'    => true,
                'sort_order'   => 3,
            ],
            [
                'name'         => 'Executive VIP Hajj Package',
                'slug'         => 'executive-vip-hajj-package',
                'category'     => 'hajj',
                'tagline'      => 'VIP upgraded air-conditioned Mina tents, 5-star hotels, and concierge logistics.',
                'description'  => 'Ultimate comfort for the sacred pilgrimage. Upgraded VIP air-conditioned tents in Mina closer to Jamarat, 5-star luxury hotels in Makkah and Madinah, full board dining, express Mashaaer high-speed train or private coach, and personal assistance.',
                'price_range'  => 'Custom VIP Inquiry',
                'duration'     => '18 to 25 Days',
                'highlights'   => [
                    'VIP Category A Mina upgraded air-conditioned tents',
                    '5-Star luxury hotels in Makkah & Madinah',
                    'Haramain high-speed train / VIP bus transport',
                    'Full-board gourmet buffet catering throughout',
                    'Doctor, religious guides, and 24/7 dedicated attendants',
                    'Complete documentation and express airport reception',
                ],
                'is_featured'  => true,
                'is_active'    => true,
                'sort_order'   => 4,
            ],

            // International Travel Packages
            [
                'name'         => 'Dubai & UAE Leisure Vacation',
                'slug'         => 'dubai-uae-leisure-vacation',
                'category'     => 'international',
                'tagline'      => 'Experience the best of Dubai: luxury shopping, desert adventures, and modern wonders.',
                'description'  => 'Exciting international holiday for individuals, couples, and families. Includes UAE tourist visa, return flights, 4-star city or beach hotel stay, desert safari with BBQ dinner, Marina dhow cruise, and Dubai city tour.',
                'price_range'  => 'From ₦1,950,000 / person',
                'duration'     => '7 Days / 6 Nights',
                'highlights'   => [
                    'UAE 30-Day tourist visa with insurance',
                    'Return flight tickets on top carriers',
                    '4-star hotel stay with daily buffet breakfast',
                    'Desert Safari with Dune Bashing, Quad bikes & BBQ',
                    'Dubai Marina luxury Dhow dinner cruise',
                    'Private airport pick-up & drop-off',
                ],
                'is_featured'  => true,
                'is_active'    => true,
                'sort_order'   => 5,
            ],
            [
                'name'         => 'United Kingdom & Europe Holiday',
                'slug'         => 'uk-europe-holiday',
                'category'     => 'international',
                'tagline'      => 'Explore iconic British and European cities with professional visa assistance and flights.',
                'description'  => 'Curated European vacation experience covering London, Paris, or Mediterranean gateways. Comprehensive standard visitor / Schengen visa advisory, verified flight itineraries, hotel bookings, and flexible sightseeing excursions.',
                'price_range'  => 'From ₦3,500,000 / person',
                'duration'     => '10 Days / 9 Nights',
                'highlights'   => [
                    'Comprehensive Visa advisory and document vetting',
                    'Return flight reservations on premium airlines',
                    'Centrally located hotel accommodations',
                    'Intercity rail passes or scenic tour coach',
                    'Key landmark entry passes & city excursions',
                    'Travel medical insurance coverage',
                ],
                'is_featured'  => true,
                'is_active'    => true,
                'sort_order'   => 6,
            ],
            [
                'name'         => 'Turkey & Egypt Heritage Voyage',
                'slug'         => 'turkey-egypt-heritage-voyage',
                'category'     => 'international',
                'tagline'      => 'A mesmerizing journey through Istanbul Bosphorus and Ancient Pyramids of Cairo.',
                'description'  => 'Cross the bridge between East and West. Discover Istanbul’s historical mosques, grand bazaar, and Bosphorus cruises, coupled with Cairo’s Giza pyramids, museums, and Nile dinner cruises.',
                'price_range'  => 'From ₦2,850,000 / person',
                'duration'     => '9 Days / 8 Nights',
                'highlights'   => [
                    'Dual-country tourist visa guidance & processing',
                    'International & connecting flights included',
                    '4-Star boutique and luxury hotel stays',
                    'Guided Giza Pyramids & Egyptian Museum tours',
                    'Scenic Bosphorus sunset cruise in Istanbul',
                    'All airport and private intercity transportation',
                ],
                'is_featured'  => true,
                'is_active'    => true,
                'sort_order'   => 7,
            ],
            [
                'name'         => 'Custom International Flight & Visa Package',
                'slug'         => 'custom-international-travel',
                'category'     => 'international',
                'tagline'      => 'Tailor-made itineraries for business, academic, medical, or leisure travels worldwide.',
                'description'  => 'Tell us your destination anywhere across Africa, Asia, Europe, or the Americas. Our travel specialists handle visa appointments, ticket issuing, hotel vouchers, and custom logistics tailored to your schedule.',
                'price_range'  => 'Custom Quote on Request',
                'duration'     => 'Customizable',
                'highlights'   => [
                    'Worldwide visa consultation & appointment booking',
                    'Direct & discounted international flight tickets',
                    'Verified hotel & serviced apartment vouchers',
                    'Corporate, family, and group group packages',
                    'Medical and academic travel facilitation',
                    '24/7 dedicated travel advisor',
                ],
                'is_featured'  => true,
                'is_active'    => true,
                'sort_order'   => 8,
            ],
        ];

        foreach ($packages as $pkg) {
            static::updateOrCreate(['slug' => $pkg['slug']], $pkg);
        }
    }
}
