<?php

namespace Cervantes\Tests\Unit;

use Cervantes\Helpers;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testUploadFileNamePrefixesTimestampAndKeepsBaseName(): void
    {
        $this->assertSame(
            '1000_report.pdf',
            Helpers::uploadFileName('report.pdf', 1000)
        );
    }

    public function testUploadFileNameStripsDirectoryComponents(): void
    {
        $this->assertSame(
            '42_photo.png',
            Helpers::uploadFileName('/tmp/uploads/photo.png', 42)
        );
    }

    public function testUploadFileNameUsesCurrentTimeWhenTimestampOmitted(): void
    {
        $before = time();
        $name = Helpers::uploadFileName('doc.docx');
        $after = time();

        [$prefix, $base] = explode('_', $name, 2);

        $this->assertSame('doc.docx', $base);
        $this->assertGreaterThanOrEqual($before, (int) $prefix);
        $this->assertLessThanOrEqual($after, (int) $prefix);
    }

    /**
     * @dataProvider validIdProvider
     */
    public function testIsValidIdAcceptsNumericValues(mixed $value): void
    {
        $this->assertTrue(Helpers::isValidId($value));
    }

    public static function validIdProvider(): array
    {
        return [
            'integer' => [5],
            'numeric string' => ['12'],
            'zero' => [0],
            'numeric with decimals' => ['3.0'],
        ];
    }

    /**
     * @dataProvider invalidIdProvider
     */
    public function testIsValidIdRejectsNonNumericValues(mixed $value): void
    {
        $this->assertFalse(Helpers::isValidId($value));
    }

    public static function invalidIdProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'alpha string' => ['abc'],
            'mixed string' => ['12abc'],
        ];
    }

    public function testToIdCastsNumericStringToInt(): void
    {
        $this->assertSame(12, Helpers::toId('12'));
    }

    public function testToIdTruncatesTrailingNonNumeric(): void
    {
        $this->assertSame(7, Helpers::toId('7 OR 1=1'));
    }

    public function testLoginRedirectSendsSuperAdminToAdminDashboard(): void
    {
        $this->assertSame(
            'admin/dashboard.php',
            Helpers::loginRedirect('SUPER_ADMIN')
        );
    }

    /**
     * @dataProvider nonSuperAdminRoleProvider
     */
    public function testLoginRedirectSendsOtherRolesToLguDashboard(string $role): void
    {
        $this->assertSame(
            'lgu/dashboard.php',
            Helpers::loginRedirect($role)
        );
    }

    public static function nonSuperAdminRoleProvider(): array
    {
        return [
            'lgu admin' => ['LGU_ADMIN'],
            'empty role' => [''],
            'lowercase super admin is not privileged' => ['super_admin'],
        ];
    }

    public function testDefaultJobStatusIsOpen(): void
    {
        $this->assertSame('OPEN', Helpers::defaultJobStatus());
    }

    public function testSanitizeTextTrimsSurroundingWhitespace(): void
    {
        $this->assertSame('Juan Dela Cruz', Helpers::sanitizeText("  Juan Dela Cruz \n"));
    }

    public function testSanitizeTextHandlesNull(): void
    {
        $this->assertSame('', Helpers::sanitizeText(null));
    }
}
