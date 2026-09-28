<?php

namespace Tests\Static;

use Tests\TestCase;

/**
 * @group static-discipline
 */
class NoTemporaryWithBypassOutsideReconcilerTest extends TestCase
{
    /** @test */
    public function no_temporary_with_bypass_calls_remain_in_modules(): void
    {
        $allowed = [
            'Modules/Petro/Database/Seeders/PetroDummyDataSeeder.php',
        ];
        $violations = [];

        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('Modules'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($rii as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relPath = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
            if (str_contains($relPath, 'Modules/Petro/Services/SettlementPaymentReconciler.php')) {
                continue;
            }
            if (in_array($relPath, $allowed, true)) {
                continue;
            }

            if (str_contains(file_get_contents($file->getPathname()), 'SettlementPaymentReconciler::withBypass(')) {
                $violations[] = $relPath;
            }
        }

        $this->assertEmpty(
            $violations,
            "Temporary SettlementPaymentReconciler::withBypass calls remain:\n" . implode("\n", $violations)
        );
    }
}
