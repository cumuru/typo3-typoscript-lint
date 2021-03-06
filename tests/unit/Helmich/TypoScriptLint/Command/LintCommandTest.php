<?php

namespace Helmich\TypoScriptLint\Tests\Unit\Command;

use Helmich\TypoScriptLint\Command\LintCommand;
use Helmich\TypoScriptLint\Linter\Configuration\ConfigurationLocator;
use Helmich\TypoScriptLint\Linter\LinterConfiguration;
use Helmich\TypoScriptLint\Linter\LinterInterface;
use Helmich\TypoScriptLint\Linter\Report\File;
use Helmich\TypoScriptLint\Logging\NullLogger;
use Helmich\TypoScriptLint\Util\Finder;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class LintCommandTest
 *
 * @package Helmich\TypoScriptLint\Command
 * @covers  \Helmich\TypoScriptLint\Command\LintCommand
 */
class LintCommandTest extends \PHPUnit_Framework_TestCase
{

    /** @var LintCommand */
    private $command;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private
        $linter,
        $linterConfigurationLocator,
        $finder;

    /** @var ObjectProphecy */
    private $loggerBuilder, $eventDispatcher;

    public function setUp()
    {
        $this->linter = $this->getMockBuilder(LinterInterface::class)->getMock();
        $this->linter->expects(any())->method('lintFile')->willReturn(new File('foo.ts'));

        $this->linterConfigurationLocator = $this
            ->getMockBuilder(ConfigurationLocator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->finder = $this
            ->getMockBuilder(Finder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerBuilder = $this->prophesize('Helmich\\TypoScriptLint\\Logging\\LinterLoggerBuilder');
        $this->eventDispatcher = $this->prophesize(EventDispatcher::class);

        $this->command = new LintCommand();

        $this->command->injectLinter($this->linter);
        $this->command->injectLinterConfigurationLocator($this->linterConfigurationLocator);
        $this->command->injectLoggerBuilder($this->loggerBuilder->reveal());
        $this->command->injectFinder($this->finder);
        $this->command->injectEventDispatcher($this->eventDispatcher->reveal());
    }

    private function runCommand(InputInterface $in, OutputInterface $out)
    {
        $class = new \ReflectionClass($this->command);

        $method = $class->getMethod('execute');
        $method->setAccessible(true);
        $method->invoke($this->command, $in, $out);
    }

    /**
     * @expectedException \Helmich\TypoScriptLint\Exception\BadOutputFileException
     */
    public function testCommandThrowsExceptionWhenBadOutputFileIsGiven()
    {
        $in = $this->createMock(InputInterface::class);
        $in->expects($this->any())->method('getOption')->willReturnMap(
            [
                ['output', null],
                ['format', 'txt'],
                ['config', 'config.yml'],
            ]
        );
        $in->expects($this->once())->method('getArgument')->with('paths')->willReturn(['foo.ts']);

        $out = $this->createMock(OutputInterface::class);

        $this->runCommand($in, $out);
    }

    public function testCommandCallsLinterWithCorrectDependencies()
    {
        $in = $this->createMock(InputInterface::class);
        $in->expects($this->any())->method('getArgument')->with('paths')->willReturn(['foo.ts']);
        $in->expects($this->any())->method('getOption')->willReturnMap(
            [
                ['output', '-'],
                ['format', 'txt'],
                ['config', 'config.yml'],
            ]
        );

        $config = $this->getMockBuilder(LinterConfiguration::class)->disableOriginalConstructor()->getMock();
        $config->expects(any())->method('getFilePatterns')->willReturn([]);

        $logger = new NullLogger();

        $out = $this->createMock(OutputInterface::class);

        $this->linterConfigurationLocator
            ->expects(once())
            ->method('loadConfiguration')
            ->with(['config.yml'])
            ->willReturn($config);

        $this->loggerBuilder->createLogger('txt', Argument::exact($out), Argument::exact($out))->shouldBeCalled()->willReturn($logger);
        $this->finder->expects($this->once())->method('getFilenames')->willReturnArgument(0);

        $this->runCommand($in, $out);
    }
}
