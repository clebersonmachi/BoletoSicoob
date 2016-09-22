# BoletoSicoob
Gerar arquivo de remessa CNAB400 para a Sicoob (apenas cobrança no momento)

## Instruções

Utilizado Models do Laravel 3.2.14, mas podem ser substituídos facilmente por uma consulta normal ao banco de dados.

### Alterações necessárias

Por favor altere o método abaixo, pois ele é responsável em buscar as informações no banco de dados

	public static function mkFinanceiroModel($completo = true){

		$dataInicio = date('Y-m-').'01';
		$dataFim = date('Y-m-t');

		if($completo){
			$model = Financeiro::with('contato');
		}else{
			$model = new Financeiro;
		}

		return $model->where_not_null('boleto_codigo')
					->where_not_null('boleto_numero')
					->where_id_financeiro_forma_pagamento( Financeiro::ID_FORMA_PAGAMENTO_BOLETO ) 
					->where_ativo(1)
					->where_boleto_situacao(0)
					->where_situacao(0)
					->where_tipo(1)
					->where_between('data_vencimento', $dataInicio, $dataFim);

	}
	

No metodo abaixo, é necessário buscar o endereço do cliente, que no meu caso, está em uma tabela separada. Por isto, é provavel que seja necessário alterar esta parte também:

	protected function gerarSegmentos($boleto) {
		
		// verifica os dados
		// --------------------------------------------------------------
		$cliente = $boleto->contato;
		if(!$cliente){
			return;
		}

		$cliente_cidade = $cliente->cidade()->first();
		$cliente_estado = $cliente->estado()->first();

		if(!$cliente_cidade or !$cliente_estado){
			throw new Exception("Erro ao gerar seguimento do boleto {$boleto->id}, informações inválidas", 1);
		}
		
		[...]
	}


Se você não está utilizando o Laravel, por favor substitua a Classe File pelas funções nativas do PHP

#### Criar pastas

File::mkdir($pasta);

substituir por: 

mkdir($pasta, 0700);

#### Criar arquivo

File::put($arquivo, $arquivoConteudo);

substituir por: 

file_put_contents($arquivo, $arquivoConteudo, LOCK_EX);


### Modo de utilizar

BoletoSicoob::criarArquivoRemessa();


## Softwares Utilizados

### Laravel 3.2
Caso tenha alguma dúvida, por favor consutulte a documentação do Laravel, disponível em: https://laravel3.veliovgroup.com/docs/database/fluent
