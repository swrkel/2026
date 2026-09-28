<?php

namespace Modules\Poultry\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Poultry\Entities\EggGrade;
use Modules\Poultry\Entities\VaccinationSchedule;

/**
 * Reference data a new tenant can start from.
 *
 * Run with a business id, because everything in this module is tenant scoped:
 *
 *   php artisan tinker
 *   >>> (new Modules\Poultry\Database\Seeders\PoultryDatabaseSeeder)->forBusiness(1);
 *
 * Safe to re-run: every row is matched on business_id plus a natural key, so
 * seeding twice updates rather than duplicates.
 *
 * IMPORTANT: the vaccination programme below is a widely used commercial
 * baseline, not veterinary advice. Disease pressure, vaccine manufacturer and
 * local regulation all change the correct schedule. Have a veterinarian review
 * and adjust it before relying on it operationally.
 */
class PoultryDatabaseSeeder extends Seeder
{
    public function run()
    {
        $businessId = config('poultry.seed_business_id');

        if (empty($businessId)) {
            $this->command->warn(
                'No business id given. Call forBusiness($id) instead, or set poultry.seed_business_id.'
            );

            return;
        }

        $this->forBusiness($businessId);
    }

    public function forBusiness($businessId)
    {
        $this->seedEggGrades($businessId);
        $this->seedVaccinationSchedules($businessId);

        return $this;
    }

    /**
     * Egg grades on the common EU weight bands. product_id and variation_id are
     * left null deliberately - an administrator maps each grade to a product on
     * the Egg Grades screen, because which product represents a large egg is a
     * per-tenant decision.
     */
    protected function seedEggGrades($businessId)
    {
        $grades = [
            ['name' => 'Extra large (XL)', 'code' => 'XL',  'min_weight_g' => 73, 'max_weight_g' => null, 'sort_order' => 1],
            ['name' => 'Large (L)',        'code' => 'L',   'min_weight_g' => 63, 'max_weight_g' => 72.9, 'sort_order' => 2],
            ['name' => 'Medium (M)',       'code' => 'M',   'min_weight_g' => 53, 'max_weight_g' => 62.9, 'sort_order' => 3],
            ['name' => 'Small (S)',        'code' => 'S',   'min_weight_g' => 43, 'max_weight_g' => 52.9, 'sort_order' => 4],
            ['name' => 'Pullet',           'code' => 'PUL', 'min_weight_g' => 35, 'max_weight_g' => 42.9, 'sort_order' => 5],
            // Not saleable: tracked for production accuracy, never stocked.
            ['name' => 'Cracked',          'code' => 'CRK', 'min_weight_g' => null, 'max_weight_g' => null, 'sort_order' => 6, 'is_saleable' => false],
            ['name' => 'Dirty',            'code' => 'DRT', 'min_weight_g' => null, 'max_weight_g' => null, 'sort_order' => 7, 'is_saleable' => false],
            ['name' => 'Broken / waste',   'code' => 'BRK', 'min_weight_g' => null, 'max_weight_g' => null, 'sort_order' => 8, 'is_saleable' => false],
        ];

        foreach ($grades as $grade) {
            EggGrade::updateOrCreate(
                ['business_id' => $businessId, 'name' => $grade['name']],
                array_merge(['is_saleable' => true], $grade, ['business_id' => $businessId])
            );
        }
    }

    /**
     * A baseline commercial vaccination programme.
     *
     * Broiler rows cover the short cycle; layer and pullet rows continue
     * through rearing to point of lay. Ages are days from placement, which is
     * how HealthService turns them into due dates for a given batch.
     */
    protected function seedVaccinationSchedules($businessId)
    {
        $rows = [
            // ---- Applies to every bird type ----
            ['name' => "Marek's disease",        'disease' => "Marek's",      'bird_type' => 'all',     'age_days' => 1,   'route' => 'injection_sc',   'dose' => '0.2 ml'],
            ['name' => 'Newcastle + IB (primer)','disease' => 'ND / IB',      'bird_type' => 'all',     'age_days' => 7,   'route' => 'eye_drop',       'dose' => '1 drop'],
            ['name' => 'Gumboro (IBD) 1',        'disease' => 'Gumboro',      'bird_type' => 'all',     'age_days' => 14,  'route' => 'drinking_water', 'dose' => '1 dose'],
            ['name' => 'Newcastle (booster)',    'disease' => 'Newcastle',    'bird_type' => 'all',     'age_days' => 21,  'route' => 'drinking_water', 'dose' => '1 dose'],
            ['name' => 'Gumboro (IBD) 2',        'disease' => 'Gumboro',      'bird_type' => 'all',     'age_days' => 24,  'route' => 'drinking_water', 'dose' => '1 dose'],

            // ---- Broiler finishing ----
            ['name' => 'Newcastle (La Sota)',    'disease' => 'Newcastle',    'bird_type' => 'broiler', 'age_days' => 28,  'route' => 'drinking_water', 'dose' => '1 dose'],

            // ---- Layer and pullet rearing ----
            ['name' => 'Fowl pox',               'disease' => 'Fowl pox',     'bird_type' => 'pullet',  'age_days' => 42,  'route' => 'wing_web',       'dose' => '1 stab'],
            ['name' => 'Infectious bronchitis',  'disease' => 'IB',           'bird_type' => 'pullet',  'age_days' => 56,  'route' => 'drinking_water', 'dose' => '1 dose'],
            ['name' => 'Fowl typhoid',           'disease' => 'Fowl typhoid', 'bird_type' => 'pullet',  'age_days' => 63,  'route' => 'injection_im',   'dose' => '0.5 ml'],
            ['name' => 'Newcastle (booster)',    'disease' => 'Newcastle',    'bird_type' => 'pullet',  'age_days' => 70,  'route' => 'drinking_water', 'dose' => '1 dose'],
            ['name' => 'Infectious coryza',      'disease' => 'Coryza',       'bird_type' => 'pullet',  'age_days' => 84,  'route' => 'injection_im',   'dose' => '0.5 ml'],
            ['name' => 'EDS + ND + IB (pre-lay)','disease' => 'EDS / ND / IB','bird_type' => 'pullet',  'age_days' => 112, 'route' => 'injection_im',   'dose' => '0.5 ml'],

            // ---- Layers in production ----
            ['name' => 'Newcastle (in lay)',     'disease' => 'Newcastle',    'bird_type' => 'layer',   'age_days' => 154, 'route' => 'drinking_water', 'dose' => '1 dose', 'is_mandatory' => false],
            ['name' => 'Newcastle (in lay)',     'disease' => 'Newcastle',    'bird_type' => 'layer',   'age_days' => 238, 'route' => 'drinking_water', 'dose' => '1 dose', 'is_mandatory' => false],

            // ---- Breeders ----
            ['name' => 'Fowl pox',               'disease' => 'Fowl pox',     'bird_type' => 'breeder', 'age_days' => 42,  'route' => 'wing_web',       'dose' => '1 stab'],
            ['name' => 'EDS + ND + IB (pre-lay)','disease' => 'EDS / ND / IB','bird_type' => 'breeder', 'age_days' => 112, 'route' => 'injection_im',   'dose' => '0.5 ml'],
        ];

        foreach ($rows as $row) {
            VaccinationSchedule::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'name'        => $row['name'],
                    'bird_type'   => $row['bird_type'],
                    'age_days'    => $row['age_days'],
                ],
                array_merge(
                    ['is_mandatory' => true, 'is_active' => true, 'breed_id' => null],
                    $row,
                    ['business_id' => $businessId]
                )
            );
        }
    }
}
