<?php
/**
 * New function call trailing comma sniff tests
 *
 * @package PHPCompatibility
 */

namespace PHPCompatibility\Tests\Sniffs\PHP;

use PHPCompatibility\Tests\BaseSniffTest;

/**
 * New function call trailing comma sniff tests
 *
 * @group newTrailingComma
 *
 * @covers \PHPCompatibility\Sniffs\PHP\NewTrailingCommaSniff
 *
 * @uses    \PHPCompatibility\Tests\BaseSniffTest
 * @package PHPCompatibility
 * @author  Juliette Reinders Folmer <phpcompatibility_nospam@adviesenzo.nl>
 */
class NewTrailingCommaSniffTest extends BaseSniffTest
{

    const TEST_FILE = 'sniff-examples/new_trailing_comma.php';


    /**
     * testTrailingComma
     *
     * @dataProvider dataTrailingComma
     *
     * @param int    $line The line number.
     * @param string $type The type detected.
     *
     * @return void
     */
    public function testTrailingComma($line, $type = 'function calls')
    {
        $file = $this->sniffFile(self::TEST_FILE, '7.2');
        $this->assertError($file, $line, "Trailing comma's are not allowed in {$type} in PHP 7.2 or earlier");
    }

    /**
     * Data provider.
     *
     * @see testTrailingComma()
     *
     * @return array
     */
    public function dataTrailingComma()
    {
        return array(
            array(15, 'calls to unset()'),
            array(16, 'calls to isset()'),
            array(21, 'calls to unset()'),
            array(27), // x2.
            array(33),
            array(36),
            array(38),
            array(40),
            array(44),
            array(47),
            array(49),
            array(52),
            array(62),
            array(65),
        );
    }


    /**
     * testNoFalsePositives
     *
     * @dataProvider dataNoFalsePositives
     *
     * @param int $line The line number.
     *
     * @return void
     */
    public function testNoFalsePositives($line)
    {
        $file = $this->sniffFile(self::TEST_FILE, '7.2');
        $this->assertNoViolation($file, $line);
    }

    /**
     * Data provider.
     *
     * @see testNoFalsePositives()
     *
     * @return array
     */
    public function dataNoFalsePositives()
    {
        return array(
            array(6),
            array(7),
            array(8),
            array(9),
            array(51),
            array(58),
            array(59),
            array(68),
            array(71),
        );
    }


    /**
     * Verify no notices are thrown at all.
     *
     * @return void
     */
    public function testNoViolationsInFileOnValidVersion()
    {
        $file = $this->sniffFile(self::TEST_FILE, '7.3');
        $this->assertNoViolation($file);
    }

}
