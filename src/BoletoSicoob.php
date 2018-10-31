<?php

namespace ClebersonMachi;

use DateTime;

/**
* Boleto Sicoob
*/
class BoletoSicoob
{
    /**
     * @var Util
     */
	use Util;

    /**
     * @var int
     */
	protected $sequencial = 0;
	
	/**
	 * Diretório base de armazenamento dos arquivos
	 * @var string
	 */
	private $basePath;

	/**
	 * Prefixo do arquivo de remessa
	 * @var string
	 */
	private $prefixoArquivo;
	
	/**
	 * Data do arquivo
	 * @var DateTime
	 */
	private $data;
	
	// Código do banco
    const BANCO = 756;

    // Número da agencia, sem digito
    const AGENCIA = '000';
    const AGENCIA_DIGITO = '0';
    
    // Número da conta, sem digito
    const CONTA = '00000';
    const CONTA_DIGITO = '0';
    const CONTA_NOME = 'NOME DA SUA EMPRESA';

    // Número do convênio indicado no frontend
    const CONVENIO = '00000';
    const CONVENIO_DIGITO = '0';

    const CARTEIRA = 1;
    const MODALIDADE = '01';

    /**
    * Inicia a classe
    */
	public function __construct(
		string $basePath = null,
		string $prefixoArquivo = null,
		DateTime $data = null
	) {
		$this->basePath = $basePath ?? __DIR__ . DS . '..';
		$this->prefixoArquivo = $prefixoArquivo ?? 'remessa-cnab400-';
		$this->data = $data ?? new DateTime();
	}

    /**
	* Gera o Arquivo
	*
	* @param array $boletos
    * @return string
    */
    public function gerarArquivo(array $boletos = [])
    {
        $arquivoNome = $this->prefixoArquivo . $this->data->format('d-m-Y') . '.txt';
        $arquivo = "{$this->basePath}/$arquivoNome";

        if (count($boletos) === 0) {
            return;
		}

        // Gera o Cabeçalho
        $arquivoConteudo = $this->gerarHeader();
		
		$this->sequencial++;

        // Gera o Conteúdo
        foreach ($boletos as $boleto) {
            $boletoConteudo = $this->gerarSegmentos($boleto);

            // atualiza o boleto
            if ($boletoConteudo) {
                $arquivoConteudo .= $boletoConteudo;

                // atualiza a sequencia
                $this->sequencial++;

                // salva o boleto
                $boleto->boleto_situacao = 1;
                $boleto->save();
            }
        }

        // Gera o Rodapé
        $arquivoConteudo .= $this->gerarTrailers();

        // salva o arquivo
        file_put_contents($arquivo, $arquivoConteudo);
		
		return $arquivo;
    }

    /**
    * Gera uma linha do arquivo
    * --------------------------------------------------------------------
    *
    * $padrao = array()
    * -----------------------------------------------------------
    * {numero da sequencia} => array(
    * 		{Quantidade de elementos},
    * 		{Preencher com},
    * 		{Posição do preenchimento}
    * )
    * -----------------------------------------------------------
    *
    * $dados = array()
    * --------------------------------------------------------------------
    *
    * @return String
    */
    protected function mkLine($padrao, $dados, $quebrarLinha = true)
    {
        $retorno = '';

        foreach ($padrao as $i => $info) {
            list($tamanho, $substuir_por, $posicao) = $info;

            $texto = $substuir_por;
            if (isset($dados[$i])) {
                $texto = $dados[$i];
            }

            // caso seja uma string converte os caracteres especiais
            if (!is_numeric($texto)) {
                $texto = $this->caracterEspecial($texto);
            }

            // adiciona os carateres de complemento
            if ($substuir_por !== null and $posicao !== null) {
                $texto = str_pad($texto, $tamanho, $substuir_por, $posicao);
            }
            
            // elimina o que ultrapassou o tamanho máximo definido
            $texto = substr($texto, 0, $tamanho);

            $retorno .= $texto;
        }

        if (!$quebrarLinha) {
            return $retorno;
        }

        return $retorno."\r\n";
    }

    /**
    * Gera o cabeçalho do arquivo
    * @return string
    */
    protected function gerarHeader()
    {
        // número da linha
        $this->sequencial = 1;

        $padrao = array(
            1 => array(1, '0', STR_PAD_LEFT),
            2 => array(1, '0', STR_PAD_LEFT),
            3 => array(7, ' ', STR_PAD_RIGHT),
            4 => array(2, '0', STR_PAD_LEFT),
            5 => array(8, ' ', STR_PAD_RIGHT),
            6 => array(7, ' ', STR_PAD_RIGHT),
            7 => array(4, '0', STR_PAD_LEFT),
            8 => array(1, '0', STR_PAD_LEFT),
            9 => array(8, '0', STR_PAD_LEFT),
            10 => array(1, '0', STR_PAD_LEFT),
            11 => array(6, '0', STR_PAD_LEFT),
            12 => array(30, ' ', STR_PAD_RIGHT),
            13 => array(18, ' ', STR_PAD_RIGHT),
            14 => array(6, '0', STR_PAD_LEFT),
            15 => array(7, '0', STR_PAD_LEFT),
            16 => array(287, ' ', STR_PAD_RIGHT),
            17 => array(6, '0', STR_PAD_LEFT)
        );

        $dados = array(
            // Identificação do Registro Header: “0” (zero)
            1 => 0,
            // Tipo de Operação: “1” (um)
            2 => 1,
            // Identificação por Extenso do Tipo de Operação: "REMESSA"
            3 => 'REMESSA',
            // Identificação do Tipo de Serviço: “01” (um)
            4 => 1,
            // Identificação por Extenso do Tipo de Serviço: “COBRANÇA”
            5 => 'COBRANÇA',
            // Complemento do Registro: Brancos
            6 => '',
            // Prefixo da Cooperativa
            7 => static::AGENCIA,
            // Dígito Verificador do Prefixo
            8 => static::AGENCIA_DIGITO,
            // Código do Cliente/Beneficiário
            9 => static::CONVENIO,
            // Dígito Verificador do Código
            10 => static::CONVENIO_DIGITO,
            // Número do convênio líder: Brancos
            11 => '',
            // Nome do Beneficiário
            12 => static::CONTA_NOME,
            // Identificação do Banco: "756BANCOOBCED"
            13 => '756BANCOOBCED',
            // Data da Gravação da Remessa: formato ddmmaa
            14 => date('dmy'),
            // Seqüencial da Remessa: número seqüencial acrescido de 1 a cada remessa. Inicia com "0000001"
            15 => 1,
            // Complemento do Registro: Brancos
            16 => '',
            // Seqüencial do Registro:”000001”
            17 => $this->sequencial
        );

        return $this->mkLine($padrao, $dados);
    }

    /**
    * Gera o Rodapé
    * @return string
    */
    protected function gerarTrailers()
    {
        $padrao = array(

            1 => array(1, '0', STR_PAD_LEFT),
            2 => array(193, ' ', STR_PAD_RIGHT),
            3 => array(40, ' ', STR_PAD_RIGHT),
            4 => array(40, ' ', STR_PAD_RIGHT),
            5 => array(40, ' ', STR_PAD_RIGHT),
            6 => array(40, ' ', STR_PAD_RIGHT),
            7 => array(40, ' ', STR_PAD_RIGHT),
            8 => array(6, '0', STR_PAD_LEFT),

        );

        $dados = array(

            1 => 9,
            2 => '',
            3 => '',
            4 => '',
            5 => '',
            6 => '',
            7 => '',
            8 => $this->sequencial,

        );

        return $this->mkLine($padrao, $dados, false);
    }

    /**
    * Gera um boleto
    * @return string
    */
    protected function gerarSegmentos($boleto)
    {
        // verifica os dados
        // --------------------------------------------------------------
        $cliente = $boleto->contato;
		
		if (!$cliente) {
            return;
        }

        $cliente_cidade = $cliente->cidade()->first();
        $cliente_estado = $cliente->estado()->first();

        if (!$cliente_cidade or !$cliente_estado) {
            throw new Exception("Erro ao gerar seguimento do boleto {$boleto->id}, informações inválidas", 1);
        }

        // padrões
        // -------------------------------------------------------------
        $padrao = array(

            1 => array(1, '0', STR_PAD_LEFT),
            2 => array(2, '0', STR_PAD_LEFT),
            3 => array(14, '0', STR_PAD_LEFT),
            4 => array(4, '0', STR_PAD_LEFT),
            5 => array(1, '0', STR_PAD_LEFT),
            6 => array(8, '0', STR_PAD_LEFT),
            7 => array(1, '0', STR_PAD_LEFT),
            8 => array(6, '0', STR_PAD_LEFT),
            9 => array(25, '0', STR_PAD_LEFT),
            10 => array(12, '0', STR_PAD_LEFT),
            11 => array(2, '0', STR_PAD_LEFT),
            12 => array(2, '0', STR_PAD_LEFT),
            13 => array(3, ' ', STR_PAD_RIGHT),
            14 => array(1, ' ', STR_PAD_RIGHT),
            15 => array(3, ' ', STR_PAD_RIGHT),
            16 => array(3, '0', STR_PAD_LEFT),
            17 => array(1, '0', STR_PAD_LEFT),
            18 => array(5, '0', STR_PAD_LEFT),
            19 => array(1, '0', STR_PAD_LEFT),
            20 => array(6, '0', STR_PAD_LEFT),
            21 => array(4, ' ', STR_PAD_RIGHT),
            22 => array(1, '0', STR_PAD_LEFT),
            23 => array(2, '0', STR_PAD_LEFT),
            24 => array(2, '0', STR_PAD_LEFT),
            25 => array(10, ' ', STR_PAD_RIGHT),
            26 => array(6, '0', STR_PAD_LEFT),
            27 => array(13, '0', STR_PAD_LEFT),
            28 => array(3, '0', STR_PAD_LEFT),
            29 => array(4, '0', STR_PAD_LEFT),
            30 => array(1, '0', STR_PAD_LEFT),
            31 => array(2, '0', STR_PAD_LEFT),
            32 => array(1, '0', STR_PAD_LEFT),
            33 => array(6, '0', STR_PAD_LEFT),
            34 => array(2, '0', STR_PAD_LEFT),
            35 => array(2, '0', STR_PAD_LEFT),
            36 => array(6, '0', STR_PAD_LEFT),
            37 => array(6, '0', STR_PAD_LEFT),
            38 => array(1, '0', STR_PAD_LEFT),
            39 => array(6, '0', STR_PAD_LEFT),
            40 => array(13, '0', STR_PAD_LEFT),
            41 => array(13, '0', STR_PAD_RIGHT),
            42 => array(13, '0', STR_PAD_LEFT),
            43 => array(2, '0', STR_PAD_LEFT),
            44 => array(14, '0', STR_PAD_LEFT),
            45 => array(40, ' ', STR_PAD_RIGHT),
            46 => array(37, ' ', STR_PAD_RIGHT),
            47 => array(15, ' ', STR_PAD_RIGHT),
            48 => array(8, '0', STR_PAD_LEFT),
            49 => array(15, ' ', STR_PAD_RIGHT),
            50 => array(2, ' ', STR_PAD_RIGHT),
            51 => array(40, ' ', STR_PAD_RIGHT),
            52 => array(2, '0', STR_PAD_LEFT),
            53 => array(1, ' ', STR_PAD_RIGHT),
            54 => array(6, '0', STR_PAD_LEFT)

        );

        // dados
        // -------------------------------------------------------------

        $dados = array();

        // Identificação do Registro Detalhe: 1 (um)
        $dados[1] = 1;
        
        // Tipo de Inscrição do Beneficiário: "01" = CPF "02" = CNPJ
        $dados[2] = 2;
        
        // Número do CPF/CNPJ do Beneficiário
        $dados[3] = static::apenasNumeros(Conf::get('cnpj'));
        
        // Prefixo da Cooperativa
        $dados[4] = static::AGENCIA;
        
        // Dígito Verificador do Prefixo
        $dados[5] = static::AGENCIA_DIGITO;
        
        // Conta Corrente
        $dados[6] = static::CONTA;
        
        // Dígito Verificador da Conta
        $dados[7] = static::CONTA_DIGITO;
        
        // Número do Convênio de Cobrança do Beneficiário: "000000"
        $dados[8] = 0;
        
        // Número de Controle do Participante: Brancos
        $dados[9] = '';
        
        // Nosso Número
        $dados[10] = static::mkNossoNumero($boleto->boleto_numero);
        
        // Número da Parcela: "01" se parcela única
        $dados[11] = 1;
        
        // Grupo de Valor: "00"
        $dados[12] = 0;
        
        // Complemento do Registro: Brancos
        $dados[13] = '';
        
        // Indicativo de Mensagem ou Sacador/Avalista:
        $dados[14] = '';
        
        // Prefixo do Título: Brancos
        $dados[15] = '';
        
        // Variação da Carteira: "000"
        $dados[16] = 0;
        
        // Conta Caução: "0"
        $dados[17] = 0;
        
        // Número do Contrato Garantia: Para Carteira 1 preencher "00000"; Para Carteira 3 preencher com o  número do contrato sem DV.
        $dados[18] = 0;
        
        // DV do contrato: Para Carteira 1 preencher "0"; Para Carteira 3 preencher com o Dígito Verificador.
        $dados[19] = 0;
        
        // Numero do borderô: preencher em caso de carteira 3
        $dados[20] = 0;
        
        // Complemento do Registro: Brancos
        $dados[21] = '';
        
        // Tipo de Emissão: 1 - Cooperativa 2 - Cliente
        $dados[22] = 2;
        
        // Carteira/Modalidade: 01 = Simples Com Registro 03 = Garantida Caucionada
        $dados[23] = 1;
        
        // Comando/Movimento:
        // 01 = Registro de Títulos
        // 02 = Solicitação de Baixa
        // 04 = Concessão de Abatimento
        // 05 = Cancelamento de Abatimento
        // 06 = Alteração de Vencimento
        // 08 = Alteração de Seu Número
        // 09 = Instrução para Protestar
        // 10 = Instrução para Sustar Protesto
        // 11 = Instrução para Dispensar Juros
        // 12 = Alteração de Pagador
        // 31 = Alteração de Outros Dados
        // 34 = Baixa - Pagamento Direto ao Beneficiário
        $dados[24] = 1;
        
        // Seu Número/Número atribuído pela Empresa
        $dados[25] = $boleto->boleto_numero;
        
        // Data Vencimento: Formato DDMMAA
        // Normal "DDMMAA"
        // A vista = "888888"
        // Contra Apresentação = "999999"
        $data = $boleto->data_vencimento;
        $data = date('dmy', strtotime($data));
        $dados[26] = $data;
        
        // Valor do Titulo
        $dados[27] = number_format($boleto->valor, 2, '', '');
        
        // Número Banco: "756"
        $dados[28] = static::BANCO;
        
        // Prefixo da Cooperativa
        $dados[29] = static::AGENCIA;
        
        // Dígito Verificador do Prefixo
        $dados[30] = static::AGENCIA_DIGITO;
        
        // Espécie do Título :
        // 01 = Duplicata Mercantil
        // 02 = Nota Promissória
        // 03 = Nota de Seguro
        // 05 = Recibo
        // 06 = Duplicata Rural
        // 08 = Letra de Câmbio
        // 09 = Warrant
        // 10 = Cheque
        // 12 = Duplicata de Serviço
        // 13 = Nota de Débito
        // 14 = Triplicata Mercantil
        // 15 = Triplicata de Serviço
        // 18 = Fatura
        // 20 = Apólice de Seguro
        // 21 = Mensalidade Escolar
        // 22 = Parcela de Consórcio
        // 99 = Outros
        $dados[31] = 1;
        
        // Aceite do Título: "0" = Sem aceite "1" = Com aceite
        $dados[32] = 0;
        
        // Data de Emissão do Título: formato ddmmaa
        $dados[33] = date('dmy');
        
        // Primeira instrução codificada:
        // Regras de impressão de mensagens nos boletos:
        // * Primeira instrução (SEQ 34) = 00 e segunda (SEQ 35) = 00, não imprime nada.
        // * Primeira instrução (SEQ 34) = 01 e segunda (SEQ 35) = 01, desconsidera-se as instruções CNAB e imprime as mensagens relatadas no trailler do arquivo.
        // * Primeira e segunda instrução diferente das situações acima, imprimimos o conteúdo CNAB:
        //   00 = AUSENCIA DE INSTRUCOES
        //   01 = COBRAR JUROS
        //   03 = PROTESTAR 3 DIAS UTEIS APOS VENCIMENTO
        //   04 = PROTESTAR 4 DIAS UTEIS APOS VENCIMENTO
        //   05 = PROTESTAR 5 DIAS UTEIS APOS VENCIMENTO
        //   07 = NAO PROTESTAR
        //   10 = PROTESTAR 10 DIAS UTEIS APOS VENCIMENTO
        //   15 = PROTESTAR 15 DIAS UTEIS APOS VENCIMENTO
        //   20 = PROTESTAR 20 DIAS UTEIS APOS VENCIMENTO
        //   22 = CONCEDER DESCONTO SO ATE DATA ESTIPULADA
        //   42 = DEVOLVER APOS 15 DIAS VENCIDO
        //   43 = DEVOLVER APOS 30 DIAS VENCIDO
        $dados[34] = 0;
        
        // Segunda instrução: vide SEQ 33
        $dados[35] = 0;
        
        // Taxa de mora mês Ex: 022000 = 2,20%)
        $dados[36] = 0;
        
        // Taxa de multa Ex: 022000 = 2,20%)
        $dados[37] = 0;
        
        // Tipo Distribuição 1 – Cooperativa 2 - Cliente
        $dados[38] = 2;
        
        // Data primeiro desconto:
        // Informar a data limite a ser observada pelo cliente para o pagamento do título com Desconto no formato ddmmaa.
        // Preencher com zeros quando não for concedido nenhum desconto.
        // Obs: A data limite não poderá ser superior a data de vencimento do título.
        $dados[39] = 0;
        
        // Valor primeiro desconto:
        // Informar o valor do desconto, com duas casa decimais.
        // Preencher com zeros quando não for concedido nenhum desconto.
        $dados[40] = 0;
        
        // 193-193 – Código da moeda
        // 194-205 – Valor IOF / Quantidade Monetária: "000000000000"
        // Se o código da moeda for REAL, o valor restante representa o IOF. Se o código da moeda for diferente de REAL, o valor restante será a quantidade monetária.
        $dados[41] = 9;
        
        // Valor Abatimento
        $dados[42] = 0;
        
        // Tipo de Inscrição do Pagador: "01" = CPF "02" = CNPJ
        $dados[43] = $cliente->pessoa == 1 ? 2 : 1;
        
        // Número do CNPJ ou CPF do Pagador
        $dados[44] = static::apenasNumeros($cliente->documento);
        
        // Nome do Pagador
        $dados[45] = $cliente->nome;
        
        // Endereço do Pagador
        $dados[46] = "{$cliente->endereco_rua} {$cliente->endereco_numero}";
        
        // Bairro do Pagador
        $dados[47] = $cliente->endereco_bairro;
        
        // CEP do Pagador
        $dados[48] = static::apenasNumeros($cliente->endereco_cep);
        
        // Cidade do Pagador
        $dados[49] = $cliente_cidade->nome;
        
        // UF do Pagador
        $dados[50] = $cliente_estado->uf;
        
        // Observações/Mensagem ou Sacador/Avalista:
        // Quando o SEQ 14 – Indicativo de Mensagem ou Sacador/Avalista - for preenchido com Brancos, as informações constantes desse campo serão impressas no campo “texto de responsabilidade da Empresa”, no Recibo do Sacado e na Ficha de Compensação do boleto de cobrança.
        // Quando o SEQ 14 – Indicativo de Mensagem ou Sacador/Avalista - for preenchido com “A” , este campo deverá ser preenchido com o nome/razão social do Sacador/Avalista
        $dados[51] = '';
        
        // Número de Dias Para Protesto:
        // Quantidade dias para envio protesto. Se = "0", utilizar dias protesto padrão do cliente cadastrado na cooperativa.
        $dados[52] = 0;
        
        // Complemento do Registro: Brancos
        $dados[53] = '';

        // Seqüencial do Registro: Incrementado em 1 a cada registro
        $dados[54] = $this->sequencial;

        return $this->mkLine($padrao, $dados);
    }

    /**
    * Cria o arquivo sem precisar iniciar a classe
    * @return string
    */
    public static function criarArquivoRemessa()
    {
        $self = new static;
		
		return $self->gerarArquivo();
    }

    /**
    * Cria o nosso número de acordo com as regras do banco Sicoob
    * @return string
    */
    public static function mkNossoNumero($numero)
    {
        $agencia = static::AGENCIA;
        $convenio = static::CONVENIO.static::CONVENIO_DIGITO;
        
        $nossoNumero = static::formataNumDoc($numero, 7);

        $sequencia = static::formataNumDoc($agencia, 4);
        $sequencia .= static::formataNumDoc($convenio, 10);
        $sequencia .= static::formataNumDoc($nossoNumero, 7);
        
        $cont = 0;
        $calculoDv = '';

        // constante fixa Sicoob » 3197
        for ($num = 0; $num <= strlen($sequencia); $num++) {
            $cont++;
            
            if ($cont == 1) {
                $constante = 3;
            }
            
            if ($cont == 2) {
                $constante = 1;
            }
            
            if ($cont == 3) {
                $constante = 9;
            }
            
            if ($cont == 4) {
                $constante = 7;
                $cont = 0;
            }

            $calculoDv = $calculoDv + (substr($sequencia, $num, 1) * $constante);
        }

        $Resto = $calculoDv % 11;
        $Dv = 11 - $Resto;
        
        if ($Dv > 9) {
            $Dv = 0;
        }
        
        return $nossoNumero . $Dv;
    }
}
