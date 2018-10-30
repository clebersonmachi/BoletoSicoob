<?php

namespace Tests;

use BoletoSicoob;
use IntegracaoFinanceiro;
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
     * @covers \BoletoSicoob::__construct
     * @covers \BoletoSicoob::gerarArquivo
     */
    public function gerarArquivoSemBoletosRetornaVazio()
    {
        $arquivo = $this->boletoObj->gerarArquivo();

        $this->assertEmpty($arquivo);
    }

    /**
     * @test
     * @covers \IntegracaoFinanceiro::mkFinanceiroModel
     * @covers \BoletoSicoob::__construct
     * @covers \BoletoSicoob::gerarArquivo
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
     * @covers \BoletoSicoob::__construct
     * @covers \BoletoSicoob::mkNossoNumero
     * @expectedException \ErrorException
     */
    public function mkNossoNumeroDisparaExcessaoParaNaoNumericos()
    {
        $this->boletoObj->mkNossoNumero('0');
    }

    /**
     * @test
     * @covers \BoletoSicoob::__construct
     * @covers \BoletoSicoob::mkNossoNumero
     */
    public function mkNossoNumeroRetornaString()
    {
        $this->markTestIncomplete('Passar número valido');

        $nossonumero = $this->boletoObj->mkNossoNumero(1234567);

        $this->assertNotEmpty($nossonumero);
    }
}
