<?php

if ( !defined('PATH_LOG') )
	define('PATH_LOG', realpath('..' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR );

//session_cache_expire(10); //sessão expira em 10 minutos
session_start();

//se o cache não tiver inicializado, incializa
/*$MEM_USERS = apc_fetch('mem_users');
if ( $MEM_USERS === FALSE )
{
	apc_clear_cache();
	apc_store('mem_users', array());
}
$MEM_USERS = apc_fetch('mem_users');*/

$notModules = array();

class Session { 
	public static $message='';
	public static $_ignoreException = false;
	public static $users  =array();
	
	public static function create($id, $userCode, $userName, $perfilMod, $perfilTab, $negaPerfilMod, $negaPerfilTab, $maxtime=0)
	{ 
		//global $MEM_USERS;
		
		//retira os módulos que são negados, da lista de módulos permitidos
		foreach ($negaPerfilMod as $key=>$value)
		{
			if ( array_key_exists($key, $perfilMod) )
			{
				//se a negação é específica de métodos [I,U,D,S]
				if ( $value!= '')
				{
					$v = $perfilMod[$key];
					
					//quando o módulo está em branco, só tem o nome do módulo, significa que tem permissão para tudo. ex: MOD = MOD[S,I,U,D]
					if ($v=='')	$v='SIUD';
					
					for ($i=0; $i<strlen($value); $i++)
					{
						$c = substr($value,$i,1);
						$v = str_replace($c, '', $v);
					}
					
					//se retirou todas as permissões que tinha, não tem permissão nenhuma, por isso exclui.
					if ($v=='')
						unset( $perfilMod[$key] );
					else
						$perfilMod[$key] = $v;
				}
				else
				{
					unset( $perfilMod[$key] );
				}
			}
		}
		
		session_start();
		
		if ( isset($_SESSION['userCodeEx']) )
		{
			if ( $_SESSION['userCodeEx']!== $userCode )
				exception('Usuário e/ou senha inválida!<br><br><b>Você deve usar login e senha do usuário atualmente logado.<b>');
		}
		
		$maxtime = 4;
		
		$_SESSION['ip']          = $_SERVER['REMOTE_ADDR'];
		$_SESSION["sessiontime"] = time();
		
		$_SESSION["id"]       = $id;
		$_SESSION["userCode"] = $userCode;
		$_SESSION["userName"] = $userName;
		$_SESSION["mod_p"]    = $perfilMod;
		$_SESSION["mod_n"]    = $negaPerfilMod;
		$_SESSION['tab_p']    = $perfilTab;		
		$_SESSION['tab_n']    = $negaPerfilTab;
		
		$_SESSION["s_maxtimer"]= $maxtime;
			
		/*$MEM_USERS[$userCode] = array(
			'ip' => $_SERVER['REMOTE_ADDR'],
		    'tm' => time(),
			'mt' => $maxtime,
			'id' => $id,			
			'nm' => $userName
		); 
		
		apc_store( 'mem_users', $MEM_USERS );
		*/
		/*$content = ___session_define_content();
		$file    = ___session_filename();
		
		create_file($file, $content);*/
		
		return true;
	}
	
	public static function ignoreException($value)
	{
		Session::$_ignoreException = $value;
	}
	
	public function perfilToArray($perfil)
	{
		$array0 = array();
		$array1 = explode(";", $perfil);
		
		$pv = '';
		
		foreach ($array1 as $m)
		{
			if ($m!='')
			{
				//obtém as permissões do módulo
				$array2 = explode('[', $m);
				
				$p = str_replace($array2[0], '', $m);
				$p = str_replace('[', '', $p);
				$p = str_replace(']', '', $p);
				$p = trim($p);
				
				$key = trim($array2[0]);
				
				if (key_exists($key, $array0))
				{
					$p .= ($array0[$key]=='' ? '' : ',') . $array0[$key];
				}
				
				$array0[$key] = $p;
			}
		}
		
		//retira os repetidos (I,U,D)
		foreach ($array0 as $k=>$v)
		{
			$a = explode(',', $v);
			sort($a);
			
			$s ='';
			
			foreach ($a as $b)
				$s .= $b;
			
			$s = str_replace('II', 'I', $s);
			$s = str_replace('UU', 'U', $s);
			$s = str_replace('DD', 'D', $s);
			
			$array0[$k] = $s;
		}
		
		return $array0;
	}
	
	public static function destroy()
	{
		$file = ___session_filename();
		
		if (file_exists($file))
			unlink($file);
		
		session_destroy();
	}
	
	/**
	 * 
	 * @param $mods string
	 * @param $per array
	 * @return boolean
	 */
	private static function analizePemission($mods, $per)
	{
		global $notModules;
		$notModules = array();
		
		if ($mods=='')
			return true;
		
		/*if ( !is_array($per) )
			return false;*/
			
		$modulos_permitidos = $per;
		$modulos_verificar = array();
		
		//converte $mods em array do tipo array(MO1=>permissões, MO2=>permissões, ...);
		$a1 = explode(';', $mods);
		foreach($a1 as $m)
		{
			$a2 = explode('[', $m);
			
			$p = str_replace($a2[0], '', $m);
			$p = str_replace('[',    '', $p);
			$p = str_replace(']',    '', $p);
			$p = str_replace(',',    '', $p);
			
			$modulos_verificar[ $a2[0] ] = $p;
		}
		
		if ( $modulos_permitidos=='' )
			$modulos_permitidos = array();
		
		foreach ($modulos_permitidos as $m1=>$p1)
		{
			if ($m1=='ALL')
				return true;
		}
			
		foreach ($modulos_verificar as $m1=>$p1)
		{
			foreach ($modulos_permitidos as $m2=>$p2)
			{
				if ($m2==$m1)
				{   //exception($modulos_verificar);
					if ( $p2!='')
					{	
						$q=0;
						//o módulo é permitido, verifica se tem as permissões I,U,D
						for ($i==0; $i<strlen($p1); $i++ )
							if ( strpos($p2, $p1[$i]) !== false )
								$q++;
						
						//para que seja permitido o acesso todas as permissões de $modulos_verificar devem existir em $modulos_permitidos
						$r = ( $q==strlen($p1) );
						if ( !$r )
						{
							if ( !in_array($m1, $notModules))
								array_push( $notModules, $m1 );
						}
						
						return $r;
					}
					
					return true;
				}
				if ( !in_array($m1, $notModules))
					array_push( $notModules, $m1 );
				//return false;
			}
		}
		
		return false;		
	}
	
	public static function isExpired($return=false)
	{
		return false;
		global $MEM_USERS;
		
		$code    = $_SESSION['userCode'];
		$expirou = ( isset($_SESSION['userCode']) && !isset($MEM_USERS[$code]) );
		
		if ( $expirou )
		{
			if ( $return )
			{
				return true;
			}
			
			echo json_encode( array(
				'success' => false,
				'_expired'=> true,
				'message' => 'Session time has expired!',
				'_message'=> Session::$message,
				'data'    => null
			));	
			exit;
		}
		else
		{
			if ( $return )
			{
				return false;
			}
		}
		
		return;
		
		
		if ( isset($_SESSION["sessiontime"]) )
		{
			$time = time() - $_SESSION["sessiontime"];
			
			if ( $time > Session::maxtimer )
			{
				$_SESSION['userCodeEx'] = $_SESSION['userCode'];
				/*$key = $_SESSION['key'];
				$userCodeEx = $_SESSION['userCodeEx'];
				
				Session::destroy();
				session_start();
				
				$_SESSION['key'] = $key;
				$_SESSION['userCodeEx'] = $userCodeEx;*/
				
				if ( $return )
				{
					return true;
				}
				else
				{
					echo json_encode( array(
						'success' => false,
						'_expired'=> true,
						'message' => 'Session time has expired!',
						'_message'=> Session::$message,
						'data'    => null
					));
					exit;
				}
			}
			else
			  $_SESSION["sessiontime"] = time();
		}
		
		return false;
	}
	
	public static function isPermission($mods, $tabs='')
	{
		Session::isExpired();
		
		$liberado_m = Session::analizePemission( $mods, $_SESSION["mod_p"] );
		$liberado_t = Session::analizePemission( $tabs, $_SESSION["tab_p"] );
		
		if ( !$liberado_m && isset($_SESSION["id"]) )
		{
			return false;
		}
		
		if ( !$liberado_t && isset($_SESSION["id"]) )
		{
			return false;
		}
		
		return true;
	}
	
	public static function isPermissionEx($mods, $tabs='')
	{		
		$liberado_m = Session::analizePemission( $mods, $_SESSION["mod_p"] );
		$liberado_t = Session::analizePemission( $tabs, $_SESSION["tab_p"] );
		
		if ( !$liberado_m && isset($_SESSION["id"]) )
		{
			return false;
		}
		
		if ( !$liberado_t && isset($_SESSION["id"]) )
		{
			return false;
		}
		
		return true;
	}
	
	public static function autentication($mods, $tabs='')
	{
		global $notModules;
			
		if ($mods=='')
			___session_error_('Internal Server Error. Módulo indefinido para autenticação');
		
		Session::isExpired();//se o tempo já expirou
		
		//se não existe sessão
		if ( !isset($_SESSION['id']) )
		{
			___session_error_('Session not found!');
		}
		
		$liberado_m = Session::analizePemission( $mods, $_SESSION["mod_p"] );
		$np = $notModules;
		$liberado_t = Session::analizePemission( $tabs, $_SESSION["tab_p"] );
		
		if ( !$liberado_m && isset($_SESSION["id"]) )
		{
			$s = '';
			$v = '';
			foreach ($np as $m)
			{
				$s .= $v.$m;
				$v = ',';
			}

			___session_error_("Permission denied in module(s): [$s]");
		}
		
		if ( !$liberado_t && isset($_SESSION["id"]) )
		{
			___session_error_('Permission denied in database table!');
		}
	}
	
	/**
	 * Ler os usuários logados, apagando arquivos que tenha sessão expirada.
	 * estrutura do arquivo de usuário logado:
	 *   time_de_última_interação|id_usuario|nome|mensagem_destinada_ao_usu�rio
	 */
	public static function refresh()
	{
		return;
		global $MEM_USERS;
		$time = time(); 
		
		foreach ($MEM_USERS as $code=>$arrData)
		{
			//calcula o tempo atual da sessão
			$t = $time - $MEM_USERS[$code]['tm'];
			
			//se o tempo excedeu o tempo máximo, exclui o item do array
			if ( $t > $MEM_USERS[$code]['mt'] )
				unset( $MEM_USERS[$code] );
			else
				$MEM_USERS[$code]['tm'] = $time; 
		} 
		
		apc_store( 'mem_users', $MEM_USERS );
		
		return;
		
		$dir = PATH_LOG . 'online' . DIRECTORY_SEPARATOR;
		$arrFiles = array();
		$arrUsers = array();
		
		if ( !file_exists(PATH_LOG) )
			mkdir(PATH_LOG);
			
		if ( !file_exists($dir) )
			mkdir($dir);
		
		if ($handle = opendir($dir))
		{
			/* varre o diretório */
			while (false !== ($file = readdir($handle)))
			{
				if ($file !='.' && $file!='..' )
				{
					//lê o conteúdo
					$content = file_get_contents($dir.$file);
					
					//separa as colunas
					$cols = explode('|', $content);
					
					//obtém o sessiontime e o  id
					$time = time() - (int)$cols[0];
					$id   = $cols[2];
					
					//se é o arquivo do usuário atualmente logado, extrai a mensagem e atualiza o timesession
					if ( $id==$_SESSION['id'] )
					{
						//extrai a mensagem que alguém ou o sistema está enviando para o usuário, se houver.
						Session::$message = ___session_extract_message($content);
						
						//atuaiza sessiontime
						$_SESSION["sessiontime"] = time();
						
						//grava o conteúdo no arquivo de sessão do usuário
						file_put_contents( $dir.$file, ___session_define_content() );
					}
					
					//se já expirou, guarda o nome para excluir o arquivo no código mais abaixo
					if ( $time <= Session::maxtimer && $id!=$_SESSION['id'])
					{
						array_push(Session::$users, $cols[1]);
					}
					else
					{
						array_push($arrFiles,$file);
					}
				}
			}
			closedir($handle);
		}
		
		foreach ($arrFiles as $file)
			unlink($dir.$file);
	}
	
	//envia uma mensagem para um usuário, -1=todos logados
	public static function sendMessage($message, $idUser=-1)
	{
		$dir = PATH_LOG . 'online' . DIRECTORY_SEPARATOR;
		
		if ($handle = opendir($dir))
		{
			while (false !== ($file = readdir($handle)))
			{
				if ($file !='.' && $file!='..')
				{
					//lê o conteúdo
					$content = file_get_contents($dir.$file);
					
					//separa as colunas
					$cols = explode('|', $content);
					
					//obtém o sessiontime e o id e nome
					$time = $cols[0];
					$nome = $cols[1];
					$id   = $cols[2];
					$msg  = $cols[3];
					
					//não envia para ele mesmo
					if ( $dir.$file != ___session_filename() && ( $id==$idUser || $idUser==-1) )
					{
						$content = $time.'|'.$nome.'|'.$id.'|'.$msg;
						
						//se já tem mensagem, concatena
						if ( $msg!='' )
						  $content .= '|';
						
						$message = str_replace('|', '', $message);
						
						$content .= $message;
						
						//grava o conteúdo no arquivo de sessão do usuário
						file_put_contents( $dir.$file, $content );
					}
				}
			}
			closedir($handle);
		}
	}
}

function ___session_extract_message($content)
{
	$arr = explode('|', $content);
	return  $arr[3];
}

function ___session_define_content()
{
	return $_SESSION['sessiontime'] . '|' . $_SESSION["userName"] . '|' . $_SESSION['id'];
}

function ___session_filename()
{
	return PATH_LOG . 'online' .  DIRECTORY_SEPARATOR . $_SESSION['id'] . '_' . $_SESSION['ip'] .'.php';
}

function ___session_error_($msg)
{
	if ( !Session::$_ignoreException )
		exit ($msg);
}
