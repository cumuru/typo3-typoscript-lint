<?php
namespace Helmich\TypoScriptLint\Tests\Unit\Linter\ReportPrinter;

use Helmich\TypoScriptLint\Linter\Report\File;
use Helmich\TypoScriptLint\Linter\Report\Issue;
use Helmich\TypoScriptLint\Linter\Report\Report;
use Helmich\TypoScriptLint\Linter\ReportPrinter\CheckstyleReportPrinter;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Helmich\TypoScriptLint\Linter\ReportPrinter\CheckstyleReportPrinter
 * @uses   \Helmich\TypoScriptLint\Linter\Report\File
 * @uses   \Helmich\TypoScriptLint\Linter\Report\Report
 * @uses   \Helmich\TypoScriptLint\Linter\Report\Issue
 */
class CheckstyleReportPrinterTest extends \PHPUnit_Framework_TestCase
{

    const EXPECTED_XML_DOCUMENT = '<?xml version="1.0" encoding="UTF-8"?>
<checkstyle version="typoscript-lint-1.0.0">
  <file name="foobar.tys">
    <error line="123" severity="info" message="Message #1" source="foobar" column="12"/>
    <error line="124" severity="warning" message="Message #2" source="foobar"/>
  </file>
  <file name="bar.txt">
    <error line="412" severity="error" message="Message #3" source="barbaz" column="141"/>
  </file>
</checkstyle>
';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $output;

    /** @var CheckstyleReportPrinter */
    private $printer;

    public function setUp()
    {
        $this->output  = $this->getMockBuilder(OutputInterface::class)->getMock();
        $this->printer = new CheckstyleReportPrinter($this->output);

        define('APP_NAME', 'typoscript-lint');
        define('APP_VERSION', '1.0.0');
    }

    /**
     * @medium
     */
    public function testXmlReportIsCorrectlyGenerated()
    {
        $file1 = new File('foobar.tys');
        $file1->addIssue(new Issue(123, 12, 'Message #1', Issue::SEVERITY_INFO, 'foobar'));
        $file1->addIssue(new Issue(124, null, 'Message #2', Issue::SEVERITY_WARNING, 'foobar'));

        $file2 = new File('bar.txt');
        $file2->addIssue(new Issue(412, 141, 'Message #3', Issue::SEVERITY_ERROR, 'barbaz'));

        $report = new Report();
        $report->addFile($file1);
        $report->addFile($file2);

        $this->output->expects($this->once())->method('write')->with(self::EXPECTED_XML_DOCUMENT);

        $this->printer->writeReport($report);
    }
}
