<?php

namespace ClebersonMachi\Tests;

use ClebersonMachi\BoletoSicoob;
use ClebersonMachi\IntegracaoFinanceiro;
use org\bovigo\vfs\vfsStream;
use Orchestra\Testbench\TestCase;

class BoletoSicoobTest extends TestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private static $root;

    /**
     * @var BoletoSicoob
     */
    private $boletoObj;

    /**
     * Boostrap
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$root = vfsStream::setup();
    }

    /**
     * Setup
     */
    public function setUp()
    {
        parent::setUp();

        $this->boletoObj = new BoletoSicoob('/storage');
    }

    /**
     * @test
     * @covers \ClebersonMachi\BoletoSicoob::__construct
     * @covers \ClebersonMachi\BoletoSicoob::gerarArquivo
     */
    public function gerarArquivoSemBoletosRetornaVazio()
    {
        $arquivo = $this->boletoObj->gerarArquivo();

        $this->assertEmpty($arquivo);
    }

    /**
     * @test
     * @covers \ClebersonMachi\IntegracaoFinanceiro::mkFinanceiroModel
     * @covers \ClebersonMachi\BoletoSicoob::__construct
     * @covers \ClebersonMachi\BoletoSicoob::gerarArquivo
     */
    public function gerarArquivoComIntegracaoFinanceiro()
    {
        $this->markTestIncomplete('Criar base');

        $financeiro = IntegracaoFinanceiro::mkFinanceiroModel();
        $arquivo = $this->boletoObj->gerarArquivo($financeiro);

        $this->assertNotEmpty($arquivo);
    }

    /**
     * @test
     * @covers \ClebersonMachi\BoletoSicoob::__construct
     * @covers \ClebersonMachi\BoletoSicoob::mkNossoNumero
     * @expectedException \ErrorException
     */
    public function mkNossoNumeroDisparaExcessaoParaNaoNumericos()
    {
        $this->boletoObj->mkNossoNumero('0');
    }

    /**
     * @test
     * @covers \ClebersonMachi\BoletoSicoob::__construct
     * @covers \ClebersonMachi\BoletoSicoob::mkNossoNumero
     */
    public function mkNossoNumeroRetornaString()
    {
        $this->markTestIncomplete('Passar número valido');

        $nossonumero = $this->boletoObj->mkNossoNumero(1234567);

        $this->assertNotEmpty($nossonumero);
    }
}
