<?php

namespace Database\Seeders;

use App\Models\HolidayCalendar;
use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * HolidayCalendarSeeder
 *
 * Seeds national holidays (applicable to all locations) and state-specific holidays
 * for all 9 states in which NexusOS operates. This seeder is idempotent — it uses
 * updateOrCreate so it can be run multiple times safely.
 */
class HolidayCalendarSeeder extends Seeder
{
    /**
     * National holidays applicable to every location in every state.
     * These are seeded once per location using the location's id.
     */
    private array $nationalHolidays2026 = [
        ['name' => 'New Year\'s Day',         'date' => '2026-01-01'],
        ['name' => 'Republic Day',             'date' => '2026-01-26'],
        ['name' => 'Holi',                     'date' => '2026-03-14'],
        ['name' => 'Good Friday',              'date' => '2026-04-03'],
        ['name' => 'Dr. Ambedkar Jayanti',     'date' => '2026-04-14'],
        ['name' => 'Ram Navami',               'date' => '2026-03-29'],
        ['name' => 'Eid ul-Fitr',              'date' => '2026-03-31'],
        ['name' => 'Eid ul-Adha',              'date' => '2026-06-07'],
        ['name' => 'Independence Day',         'date' => '2026-08-15'],
        ['name' => 'Janmashtami',              'date' => '2026-08-22'],
        ['name' => 'Gandhi Jayanti',           'date' => '2026-10-02'],
        ['name' => 'Dussehra',                 'date' => '2026-10-22'],
        ['name' => 'Diwali',                   'date' => '2026-11-08'],
        ['name' => 'Guru Nanak Jayanti',       'date' => '2026-11-24'],
        ['name' => 'Christmas Day',            'date' => '2026-12-25'],
    ];

    /**
     * State-specific holidays keyed by 2-letter state code.
     */
    private array $stateHolidays2026 = [
        'DL' => [
            ['name' => 'Delhi Foundation Day',    'date' => '2026-02-14'],
        ],
        'HR' => [
            ['name' => 'Haryana Day',             'date' => '2026-11-01'],
            ['name' => 'Baisakhi',                'date' => '2026-04-14'],
        ],
        'MH' => [
            ['name' => 'Chhatrapati Shivaji Maharaj Jayanti', 'date' => '2026-02-19'],
            ['name' => 'Gudi Padwa',              'date' => '2026-03-19'],
            ['name' => 'Maharashtra Day',         'date' => '2026-05-01'],
            ['name' => 'Ganesh Chaturthi',        'date' => '2026-08-25'],
        ],
        'KA' => [
            ['name' => 'Karnataka Rajyotsava',    'date' => '2026-11-01'],
            ['name' => 'Ugadi',                   'date' => '2026-03-19'],
            ['name' => 'Kanaka Jayanti',          'date' => '2026-11-04'],
            ['name' => 'Hampi Utsav',             'date' => '2026-11-06'],
        ],
        'UP' => [
            ['name' => 'Uttar Pradesh Foundation Day', 'date' => '2026-01-24'],
            ['name' => 'Makar Sankranti',         'date' => '2026-01-14'],
        ],
        'GJ' => [
            ['name' => 'Gujarat Day',             'date' => '2026-05-01'],
            ['name' => 'Uttarayan (Makar Sankranti)', 'date' => '2026-01-14'],
        ],
        'WB' => [
            ['name' => 'Poila Baisakh (Bengali New Year)', 'date' => '2026-04-15'],
            ['name' => 'Durga Puja (Maha Navami)', 'date' => '2026-10-21'],
            ['name' => 'Durga Puja (Vijaya Dashami)', 'date' => '2026-10-22'],
        ],
        'JH' => [
            ['name' => 'Jharkhand Foundation Day', 'date' => '2026-11-15'],
            ['name' => 'Sarhul',                  'date' => '2026-04-02'],
        ],
        'GA' => [
            ['name' => 'Goa Liberation Day',      'date' => '2026-12-19'],
            ['name' => 'Feast of St. Francis Xavier', 'date' => '2026-12-03'],
        ],
    ];

    public function run(): void
    {
        $locations = Location::withoutGlobalScopes()->where('is_active', true)->get();

        foreach ($locations as $location) {
            // ── Seed national holidays for this location ──────────────────────
            foreach ($this->nationalHolidays2026 as $holiday) {
                HolidayCalendar::withoutGlobalScopes()->updateOrCreate(
                    [
                        'location_id'  => $location->id,
                        'holiday_date' => $holiday['date'],
                        'type'         => 'national',
                    ],
                    [
                        'holiday_name' => $holiday['name'],
                        'is_active'    => true,
                    ]
                );
            }

            // ── Seed state-specific holidays for this location ────────────────
            $stateCode = $location->state_code;
            if ($stateCode && isset($this->stateHolidays2026[$stateCode])) {
                foreach ($this->stateHolidays2026[$stateCode] as $holiday) {
                    HolidayCalendar::withoutGlobalScopes()->updateOrCreate(
                        [
                            'location_id'  => $location->id,
                            'holiday_date' => $holiday['date'],
                            'type'         => 'state',
                        ],
                        [
                            'holiday_name' => $holiday['name'],
                            'is_active'    => true,
                        ]
                    );
                }
            }
        }

        $this->command->info('HolidayCalendarSeeder: Seeded national and state-specific holidays for all active locations.');
    }
}
