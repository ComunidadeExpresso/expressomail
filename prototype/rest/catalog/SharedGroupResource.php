<?php

if (!defined('ROOTPATH'))
    define('ROOTPATH', dirname(__FILE__) . '/..');

require_once(ROOTPATH . '/rest/hypermedia/hypermedia.php');

use prototype\api\Config as Config;

class SharedGroupResource extends Resource {

    /**
     * Retorna um grupo compartilhado 
     *
     * @license    http://www.gnu.org/copyleft/gpl.html GPL
     * @author     Cons�rcio Expresso Livre - 4Linux (www.4linux.com.br) e Prognus Software Livre (www.prognus.com.br)
     * @sponsor    Caixa Econ�mica Federal
     * @author     Jos� Vicente Tezza Jr. 
     * @return     Retorna os contatos de um Grupo Compartilhado
     * @access     public
     * */
    function get($request, $id) {

	$this->secured();

	$response = new Response($request);
	$response->addHeader('Content-type', 'aplication/json');
	$response->code = Response::OK;

	$h = new Hypermedia();
	$c = new Collection($request->resources, 'SharedGroupResource');

	try {

	    //Recupera o grupo 
	    $group = Controller::read(array('concept' => 'contactGroup'), false, array('filter' => array('=', 'id', $id)));

            if (!$group) {
                $this->createException($request, $response, Response::NOTFOUND, 'Bad request', 'Resource not found.');
                return $response;
            }

	    //Proprietario do grupo
	    $ownerId = $group[0]['user'];

	    $idS = array(Config::me("uidNumber"));
	    $acl = array();

	    //Recupera o uidNumber do usu�rio que compartilhou o grupo com o usu�rio logado
	    $sql = 'SELECT acl_account as "uidNumber", acl_rights as "acl" '.
		   'FROM phpgw_acl '.
		   'WHERE (acl_location =   \'' . Config::me("uidNumber") . '\' AND acl_appname =  \'contactcenter\' AND acl_account = \''.$ownerId.'\')';
	    $shareds = Controller::service('PostgreSQL')->execResultSql($sql);

	    //Verifica o acesso definido para o usuario logado
	    $flagGroup = false;
	    if (!empty($shareds) && $shareds){
		foreach ($shareds as $s) {
		    array_push($idS, $s['uidNumber']);
		    $acl[$s['uidNumber']] = $this->decodeAcl(decbin($s['acl']));

		    //verifica se o proprietario do grupo habilitou o acesso de leitura para o usuario logado
		    if($s['uidNumber'] == $ownerId && $acl[$s['uidNumber']]['read']){
			$flagGroup = true;
		    }
		}
	    }

	    //Se o grupo nao esta compartilhado
	    if(!$flagGroup){
		$this->createException($request, $response, Response::UNAUTHORIZED, 'unauthorized', 'Resource unauthorized.');
		return $response;
	    }

	    //Obtem informacoes do proprietario do grupo
	    $userOwner = Controller::read(
					   array('concept' => 'user','service'=>'OpenLDAP'), 
					   false, 
					   array('filter' => array('=', 'id', $ownerId ), 'notExternal' => true)
	    );

	    if(is_array($userOwner)){
		$userOwner = $userOwner[0];
	    }

	    //Recupera os grupos do usuario
        $groups = Controller::find(array('concept' => 'contactGroup'), false, array('filter' => array('=','id', $id), 'AND' => array('=','user', $ownerId) , 'order' => array('name')));
	    if($groups){
                foreach($groups[0]['contacts'] as $value){
                        $d = new Data();
                        $i = new Item($request->resources, 'ContactResource', $value['id']);

                        $d->setName('name');
                        $d->setValue($value['name']);
                        $d->setPrompt('Nome do Grupo');
                        $d->setDataType('string');
                        $d->setMaxLength('100');
                        $d->setMinLength(null);
                        $d->setRequired(true);

                        $i->addData($d);

                        $d = new Data();
                        $d->setName('id');
                        $d->setValue($value['id']);
                        $d->setPrompt('Id do Contato');
                        $d->setDataType('string');
                        $d->setMaxLength('100');
                        $d->setMinLength(null);
                        $d->setRequired(true);

                        $i->addData($d);

                        $d = new Data();
                        $d->setName('email');
                        $d->setValue($value['email']);
                        $d->setPrompt('Email do Contato');
                        $d->setDataType('string');
                        $d->setMaxLength('100');
                        $d->setMinLength(null);
                        $d->setRequired(true);

                        $i->addData($d);

			//Define os link baseado nas permissoes de acesso
			if(Config::me('uidNumber') != $value['user']){
				/*Descomentar ao implementar os m�todos
				if($acl[$value['user']]['delete']){
		                        $l = new Link();
		                        $l->setHref('');
        		                $l->setRel('delete');
                		        $l->setAlt('Remover');
	                	        $l->setPrompt('Remover');
        	                	$l->setRender('link');
	                	        $i->addLink($l);
				}

				if($acl[$value['user']]['update']){
		                        $l = new Link();
	        	                $l->setHref('');
        	        	        $l->setRel('put');
                	        	$l->setAlt('Atualizar');
	                	        $l->setPrompt('Atualizar');
        	                	$l->setRender('link');
		                        $i->addLink($l);
				}

				if($acl[$value['user']]['write']){
	        	                $l = new Link();
        	                	$l->setHref('');
                		        $l->setRel('post');
	                	        $l->setAlt('Criar');
        	                	$l->setPrompt('Criar novo');
	                	        $l->setRender('link');
        	                	$i->addLink($l);
				}

                                if($acl[$value['user']]['read']){
                                        $l = new Link();
                                        $l->setHref('');
                                        $l->setRel('get');
                                        $l->setAlt('Buscar');
                                        $l->setPrompt('Buscar');
                                        $l->setRender('link');
                                        $i->addLink($l);
                                }*/
			}
			else{
                        	/*Descomentar ao implementar m�todos no recurso
	                        $l = new Link();
	                        $l->setHref('');
        	                $l->setRel('delete');
                	        $l->setAlt('Remover');
	                        $l->setPrompt('Remover');
                        	$l->setRender('link');
        	                $i->addLink($l);

	                        $l = new Link();
        	                $l->setHref('');
                	        $l->setRel('put');
                        	$l->setAlt('Atualizar');
	                        $l->setPrompt('Atualizar');
        	                $l->setRender('link');
                	        $i->addLink($l);

	                        $l = new Link();
        	                $l->setHref('');
                	        $l->setRel('get');
                        	$l->setAlt('Buscar');
	                        $l->setPrompt('Buscar');
        	                $l->setRender('link');

                	        $i->addLink($l);
				*/
			}
                        $c->addItem($i);
		}
	    }

	    if (!$groups) {
		$this->createException($request, $response, Response::NOTFOUND, 'Bad request', 'Resource not found.');
		return $response;
	    }

	    $t = new Template();
            $d = new Data();

            $d->setName('name');
            $d->setValue(null);
            $d->setPrompt('Nome do Contato');
            $d->setDataType('string');
            $d->setMaxLength(100);
            $d->setMinLength(null);
            $d->setRequired(true);

            $t->addData($d);

            $d = new Data();
            $d->setName('email');
            $d->setValue(null);
            $d->setPrompt('Email do Contato');
            $d->setDataType('string');
            $d->setMaxLength(100);
            $d->setMinLength(null);
            $d->setRequired(true);

            $t->addData($d);

            $d = new Data();
            $d->setName('telefone');
            $d->setValue(null);
            $d->setPrompt('Telefone do Contato');
            $d->setDataType('string');
            $d->setMaxLength(100);
            $d->setMinLength(null);
            $d->setRequired(true);

            $t->addData($d);

            $c->setTemplate($t);

            $d = new Data();
            $d->setName('id');
            $d->setValue($groups[0]['id']);

            $d->setPrompt('Id do Grupo');
            $d->setDataType('string');
            $d->setMaxLength(100);
            $d->setMinLength(null);
            $d->setRequired(true);

            $c->addData($d);

            $d = new Data();
            $d->setName('name');
            $d->setValue($groups[0]['name']);
            $d->setPrompt('Nome do Grupo');
            $d->setDataType('string');
            $d->setMaxLength(100);
            $d->setMinLength(null);
            $d->setRequired(true);

            $c->addData($d);

            $d = new Data();
            $d->setName('email');
            $d->setValue($groups[0]['email']);
            $d->setPrompt('Email do Grupo');
            $d->setDataType('string');
            $d->setMaxLength(100);
            $d->setMinLength(null);
            $d->setRequired(true);

            $c->addData($d);

            $d = new Data();
            $d->setName('ownerId');
            $d->setValue($userOwner['id']);
            $d->setPrompt('Atributo UID (LDAP)');
            $d->setDataType('string');
            $d->setMaxLength(100);
            $d->setMinLength(null);
            $d->setRequired(true);

	    $c->addData($d);

            $d = new Data();
            $d->setName('ownerName');
            $d->setValue($userOwner['name']);
            $d->setPrompt('Atributo cn (LDAP)');
            $d->setDataType('string');
            $d->setMaxLength(100);
            $d->setMinLength(null);
            $d->setRequired(true);

            $c->addData($d);


            //Define os link baseado nas permissoes de acesso
            if(Config::me('uidNumber') != $value['user']){
                    /*Descomentar ao implementar os m�todos
                    if($acl[$value['user']]['delete']){
                          $l = new Link();
                          $l->setHref('');
                          $l->setRel('delete');
                          $l->setAlt('Remover');
                          $l->setPrompt('Remover');
                          $l->setRender('link');
                          $i->addLink($l);
                    }

                    if($acl[$value['user']]['update']){
                          $l = new Link();
                          $l->setHref('');
                          $l->setRel('put');
                          $l->setAlt('Atualizar');
                          $l->setPrompt('Atualizar');
                          $l->setRender('link');
                          $i->addLink($l);
                    }

                    if($acl[$value['user']]['write']){
                          $l = new Link();
                          $l->setHref('');
                          $l->setRel('post');
                          $l->setAlt('Criar');
                          $l->setPrompt('Criar novo');
                          $l->setRender('link');
                          $i->addLink($l);
                    }

                    if($acl[$value['user']]['read']){
                          $l = new Link();
                          $l->setHref('');
                          $l->setRel('get');
                          $l->setAlt('Buscar');
                          $l->setPrompt('Buscar');
                          $l->setRender('link');
                          $i->addLink($l);
                    }*/
            }
            else{
                    /*Descomentar ao implementar m�todos no recurso
                    $l = new Link();
                    $l->setHref('');
                    $l->setRel('delete');
                    $l->setAlt('Remover');
                    $l->setPrompt('Remover');
                    $l->setRender('link');
                    $i->addLink($l);

                    $l = new Link();
                    $l->setHref('');
                    $l->setRel('put');
                    $l->setAlt('Atualizar');
                    $l->setPrompt('Atualizar');
                    $l->setRender('link');
                    $i->addLink($l);

                    $l = new Link();
                    $l->setHref('');
                    $l->setRel('get');
                    $l->setAlt('Buscar');
                    $l->setPrompt('Buscar');
                    $l->setRender('link');

                    $i->addLink($l);
                    */
            }

            $h->setCollection($c);

	} catch (Exception $ex) {
	    $this->createException($request, $response, Response::INTERNALSERVERERROR, 'Internal Server Error', $ex);
	    return $response;
	}

	$response->body = $h->getHypermedia($request->accept[10][0]);
	return $response;
    }

    function decodeAcl($bin) {

	$acl = array();
	$bin = str_split($bin);
	$acl['read'] = (isset($bin[0]) && $bin[0] == 1) ? true : false;
	$acl['write'] = (isset($bin[1]) && $bin[1] == 1) ? true : false;
	$acl['update'] = (isset($bin[2]) && $bin[2] == 1) ? true : false;
	$acl['delete'] = (isset($bin[3]) && $bin[3] == 1) ? true : false;

	return $acl;
    }

    private function createException($request, &$response, $code, $title, $description) {
	$response->code = $code;

	$h = new Hypermedia();
	$c = new Collection($request->resources, 'DynamicContactResource');
	$e = new Error();

	$e->setCode($code);
	$e->setTitle($title);
	$e->setDescription($description);

	$c->setError($e);
	$h->setCollection($c);

	$response->body = $h->getHypermedia($request->accept[10][0]);
    }
}

?>
