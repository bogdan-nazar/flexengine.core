<?php
/* Native FlexEngine Extension Module */
namespace FlexEngine;
class module
{
	private static $__c				=	null;
	private static $__configTTL		=	300;
	private static $__ic			=	false;
	private static $__is			=	array();
	private static $__inited		=	false;
	private static $__isadmin		=	false;
	private static $__silent		=	false;

	final private static function _iClass($class)
	{
		$c=explode("\\",$class);
		return array_pop($c);
	}

	final private static function _iGet($class,$set=true,$clChk=true)
	{
		if($clChk)$class=self::_iClass($class);
		if(!$class)return($set?self::$__ic=false:false);
		foreach(self::$__is as $i)
		{
			if($i->__instance===$class)return($set?self::$__ic=$i:$i);
		}
		return($set?self::$__ic=false:false);
	}

	final private static function _iSet($class,$clChk=true)
	{
		if($clChk)$class=self::_iClass($class);
		if(self::_iGet($class,false,false))return false;
		$i					=	new \StdClass();
		$i->__config		=	array("data"=>array(),"key"=>"","time"=>0);
		$i->__instance		=	$class;
		$i->__runstage		=	-1;//0-init,1-exec,2-sleep
		$i->__version		=	array(1,0,0);
		$i->__session		=	array();
		$i->__sessionAdmin	=	array();
		self::$__is[]=self::$__ic=$i;
		return $i;
	}

	final private static function _isAdmn()
	{
		if(self::$__ic && @defined("ADMIN_MODE") && (self::$__ic->__instance===ADMIN_MODE))return false;
		return self::$__isadmin;
	}

	final private static function _session($par="",$do="get",$data=NULL)
	{
		if($par==="")
		{
			if(!self::_isAdmn())return self::$__ic->__session;
			else return self::$__ic->__sessionAdmin;
		}
		if(self::_isAdmn())
		{
			$ses=&self::$__ic->__sessionAdmin;
		}
		else
		{
			$ses=&self::$__ic->__session;
		}
		if($par===false)
		{
			$ses=array();
			return;
		}
		if($do=="get")
		{
			if($par=="config")return self::config($par);
			if(isset($ses[$par]))return $ses[$par];
			else return"";
		}
		//"set"
		else
		{
			if($par=="config")
			{
				self::msgAdd("Can't save config data directly into session.",MSGR_TYPE_ERR,true);
				return false;
			}
			if(is_null($data))return false;
			$ses[$par]=$data;
		}
	}

	final private static function _sessionRead()
	{
		if(!self::$__ic)return;
		$sesName=FLEX_APP_NAME."-".self::$__ic->__instance."-data";
		if(self::_isAdmn())
		{
			$sesName=$sesName."-admin";
			if(isset($_SESSION[$sesName]))
				self::$__ic->__sessionAdmin=unserialize($_SESSION[$sesName]);
		}
		else
		{
			if(isset($_SESSION[$sesName]))
				self::$__ic->__session=unserialize($_SESSION[$sesName]);
		}
	}

	final private static function _sessionWrite()
	{
		if(!self::$__ic)return;
		if(self::_isAdmn())
		{
			if(@count(self::$__ic->__sessionAdmin))
				$_SESSION[FLEX_APP_NAME."-".self::$__ic->__instance."-data-admin"]=serialize(self::$__ic->__sessionAdmin);
		}
		else
		{
			if(@count(self::$__ic->__session))
				$_SESSION[FLEX_APP_NAME."-".self::$__ic->__instance."-data"]=serialize(self::$__ic->__session);
		}
	}

	final protected static function _class()
	{
		return self::_iClass(@get_called_class());
	}

	final protected static function access($access="r",$entity="")
	{
		return auth::access(self::_iClass(@get_called_class()),$access,$entity);
	}

	final protected static function action($actName)
	{
		return self::$__c->action($actName);
	}

	final protected static function appRoot()
	{
		return FLEX_APP_DIR_ROOT;
	}

	final protected static function cacheCheck($class,$entity,$ttl=false,$ext="")
	{
		return cache::check($class,$entity,$ttl,$ext);
	}

	final protected static function cacheGet($class,$entity,$ttl=false,$echo=false,$ext="")
	{
		return cache::get($class,$entity,$ttl,$echo,$ext);
	}

	final protected static function cacheSet($class,$entity,$ttl,$value,$echo=false,$ext="")
	{
		return cache::set($class,$entity,$ttl,$value,$echo,$ext);
	}

	final protected static function cacheTimeout()
	{
		return cache::getTimeout();
	}

	final protected static function clientConfigAdd($data=array())
	{
		$class=self::_iClass(@get_called_class());
		return self::$__c->addConfig($class,$data);
	}

	final protected static function config($name=false,$params=false,$load=false,$core=false)
	{
		//если запрошен конфиг ядра, то запрашиваем получение и выходим
		if($core)return self::$__c->config(false,$name,$params,false);
		//определяем вызывающий модуль
		$class=@get_called_class();
		if(!self::_iGet($class))return false;
		//если запрошено обновление конфига из БД, то пытаемся выполнить...
		if($load)
		{
			//если имя параметра не задано, то делаем полное обновление
			if(!$name)
			{
				self::$__session["configTime"]=time();
				self::$__session["config"]=self::$__c->config($class);
			}
			else
			{
				self::$__session["config"]=array_merge_recursive(self::$__session["config"],self::$__c->config($class,$name));
			}
			self::$__ic->__config["data"]=array_merge_recursive(self::$__ic->__config["data"],self::$__ic->__session["config"]);
		}
		//если не задано имя параметра, возвращаем весь конфиг
		if(!$name)
		{
			if(isset(self::$__ic->__config["data"]))
			{
				if($params)return self::$__ic->__config["data"];
				else
				{
					$cfg=array();
					foreach(self::$__ic->__config["data"] as $name=>$val)
					{
						if(isset($val["value"]))$cfg[$name]=$val["value"];
						else $cfg[$name]="";
					}
					return $cfg;
				}
			}
		}
		//а если задано, то пытаемся вернуть значение параметра
		else
		{
			if($params)
			{
				if(isset(self::$__ic->__config["data"][$name]))return self::$__ic->__config["data"][$name];
				else return"";
			}
			else
			{
				if(isset(self::$__ic->__config["data"][$name]) &&
					isset(self::$__ic->__config["data"][$name]["value"]))return self::$__ic->__config["data"][$name]["value"];
				else return"";
			}
		}
	}

	final protected static function dt($dt,$full=false,$sect="-")
	{
		return lib::dt($dt,$full,$sect);
	}

	final protected static function dtR($dt,$full=false,$sect=".")
	{
		return lib::dtR($dt,$full,$sect);
	}

	final protected static function dtRValid($dt)
	{
		return lib::validDtRus($dt);
	}

	final protected static function lastErr()
	{
		return msgr::errorGet();
	}

	final protected static function lastMsg()
	{
		return msgr::lastMsg();
	}

	final protected static function libJsonMake($data,$forceObj=NULL,$escape=false,$quoted=true)
	{
		return lib::jsonMake($data,$forceObj,$escape,$quoted);
	}

	final protected static function libLastMsg()
	{
		return lib::lastMsg();
	}

	final protected static function libValidEmail($email="",$allow_empty=false)
	{
		return lib::validEmail($email,$allow_empty);
	}

	final protected static function libValidStr($str,$type,$ignoreEmpty=false,$minLen=-1,$maxLen=-1,$needMsg=false,$fldName="[?]")
	{
		return lib::validStr($str,$type,$ignoreEmpty,$minLen,$maxLen,$needMsg,$fldName);
	}

	final protected static function mailSend($mt,$ms,$mb,$mf=false,$fname="")
	{
		return msgr::mailSend($mt,$ms,$mb,$mf,$fname);
	}

	final protected static function mediaFetch($id,$childs=true)
	{
		return media::fetch($id,$childs);
	}

	final protected static function mediaFetchArray()
	{
		$args=func_get_args();
		if(!count($args) || (is_int($args[0]) || (is_string($args[0]) && (0+$args[0]>0))))
		{
			$class=str_replace(__NAMESPACE__."\\","",@get_called_class());
			$entity=isset($args[0])?$args[0]:false;
			$filters=isset($args[1])?$args[1]:array();
			$range=isset($args[2])?$args[2]:false;
			$childs=isset($args[3])?$args[3]:false;
		}
		else
		{
			$class=isset($args[0])?$args[0]:"";
			$entity=isset($args[1])?$args[1]:false;
			$filters=isset($args[2])?$args[2]:array();
			$range=isset($args[3])?$args[3]:false;
			$childs=isset($args[4])?$args[4]:false;
		}
		return media::fetchArray($class,$entity,$filters,$range,$childs);
	}

	final protected static function mediaLastMsg()
	{
		return media::lastMsg();
	}

	final protected static function modHookName($hookName)
	{
		return self::$__c->modHookName($hookName);
	}

	final protected static function modId($class,$forceDb=false)
	{
		return self::$__c->modId($class,$forceDb);
	}

	final protected static function mquotes_gpc()
	{
		return lib::mquotes_gpc();
	}

	final protected static function mquotes_runtime()
	{
		return lib::mquotes_runtime();
	}

	final protected static function msgAdd($msg,$msgType=MSGR_TYPE_INF,$msgShow=MSGR_SHOW_DIALOG)
	{
		msgr::add($msg,$msgType,$msgShow);
	}

	final protected static function page($prop="")
	{
		return content::item($prop);
	}

	final protected static function pageByModMethod($method="",$mod="")
	{
		if(!$mod)$mod=self::_iClass(@get_called_class());
		return content::pageByModMethod($mod,$method);
	}

	final protected static function pageIndex()
	{
		return content::pageIndex();
	}

	final protected static function path($prop="")
	{
		return self::$__c->path($prop);
	}

	final protected static function post($var)
	{
		return self::$__c->post($var);
	}

	final protected static function posted($var)
	{
		return self::$__c->posted($var);
	}

	final protected static function q($q,$die=false,$debug=array("msg"=>"Ошибка выполнения запроса к БД."))
	{
		return db::q($q,$die,$debug);
	}

	final protected static function qe($s)
	{
		return db::esc($s);
	}

	final protected static function qf($r,$a="a") {
		return db::fetch($r,$a);
	}

	final protected static function resourceScriptAdd($name="",$core=false,$admin=false)
	{
		if(self::$__silent)return false;
		$class=@get_called_class();
		if(!self::_iGet($class))return false;
		return render::addScript(self::$__ic->__instance,$name,false,self::_isAdmn());
	}

	final protected static function resourceStyleAdd($name="",$core=false,$admin=false)
	{
		if(self::$__silent)return false;
		$class=@get_called_class();
		if(!self::_iGet($class))return false;
		return render::addStyle(self::$__ic->__instance,$name,false,self::_isAdmn());
	}

	final private static function sessionEmpty()
	{
		if(!self::_iGet(@get_called_class()))return;
		return self::_session(false);
	}

	final private static function sessionGet($par="")
	{
		if(!self::_iGet(@get_called_class()))return;
		return self::_session($par);
	}

	final private static function sessionSet($par,$data=NULL)
	{
		if(!self::_iGet(@get_called_class()))return;
		return self::_session($par,"set",$data);
	}

	final protected static function silent()
	{
		return self::$__silent;
	}

	final protected static function silentResponseSend($data,$isJson=true,$callback=false)
	{
		return self::$__c->silentResponseSend($data,$isJson,$callback);
	}

	final protected static function silentXResponseSet($data,$isJson=true)
	{
		return self::$__c->silentXResponseSet($data,$isJson);
	}

	final protected static function tb($name)
	{
		return db::tnm($name);
	}

	final protected static function tbMeta()
	{
		$args=func_get_args();
		$c=count($args);
		if($c<3)
		{
			if(($c==1) || ($c==2 && is_bool($args[1])))
			{
				$class=self::_iClass(@get_called_class());
				$tname=$args[0];
				if($c)$force=$args[1];
				else $force=false;
			}
			else
			{
				if(!$c)$class=self::_iClass(@get_called_class());
				else $class=isset($args[0])?$args[0]:"";
				$tname=isset($args[1])?$args[1]:"";
				$force=false;
			}
		}
		else
		{
			$class=$args[0];
			$tname=$args[1];
			$force=$args[2];
		}
		return db::tMeta($class,$tname,$force);
	}

	final protected static function template()
	{
		return render::template();
	}

	final protected static function tplGet($tplSection="",$tplFile="",$useTemplatesSet="") {
		$class=self::_iClass(@get_called_class());
		return tpl::get($class,$tplSection,$tplFile,$useTemplatesSet);
	}

	final protected static function user($par="")
	{
		return auth::user($par);
	}

	final public static function __attach()
	{
		if(!self::$__c)
		{
			self::$__c=_a::core();
			self::$__silent=self::$__c->silent();
			self::$__isadmin=defined("ADMIN_MODE") && auth::admin();
			self::$__inited=true;
		}
	}

	final public static function __exec($instance,$srv=false)
	{
		//второй аргумент $srv пока что не используется
		if(!self::_iGet($instance))return;
		if(self::$__ic->__runstage>1)return;
		self::$__ic->__runstage++;
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		if(@method_exists($class,"_on2exec"))$class::_on2exec();
	}

	final public static function __init($instance,$srv=false)
	{
		//для сервисных модулей __init вызывается 2 раза:
		// 1-й: только для создания экземпляра (из метода ::__service)
		// 2-й: для собственно инициализации (из метода core->__modsStage("__init"))
		if($srv)
		{
			if(!self::_iGet($instance))
			{
				self::_iSet($instance);
				return;
			}
		}
		//если модуль не сервисный, то создаем экземпляр
		//и сразу его инициализируем
		else
		{
			if(!self::_iSet($instance))return;
		}
		if(self::$__ic->__runstage>0)return;
		self::$__ic->__runstage++;
		self::_sessionRead();
		//обрабатываем конфиг
		$cfgLoad=false;
		self::$__configTTL=self::$__c->config(false,"config-reload");
		if(isset(static::$configDefault))self::$__ic->__config["data"]=static::$configDefault;
		if(!isset(self::$__ic->__session["config"]))$cfgLoad=true;
		else
		{
			$tm=time();
			if(!isset(self::$__ic->__session["configTime"]) || ((time()-self::$__ic->__session["configTime"])>self::$configTTL))
			{
				self::$__ic->__session["configTime"]=$tm;
				self::$__ic->__session["config"]=self::$__c->config($instance);
				self::$__ic->__config["data"]=array_merge_recursive(self::$__ic->__config["data"],self::$__ic->__session["config"]);
			}
		}
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		if(@method_exists($class,"_on1init"))$class::_on1init();
		if(@method_exists($class,"_hookLangData"))lang::extend($class::_hookLangData(lang::index()));
	}

	final public static function __install($instance)
	{
		self::$__c=_a::core();
		self::$__silent=self::$__c->silent();
		self::$__isadmin=defined("ADMIN_MODE") && auth::admin();
		if(!self::_isAdmn())
		{
			msgr::add(_t("Can't install from non-admin environment."));
			return false;
		}
		if(!self::_iSet($instance))return;
		if(self::$__ic->__runstage>0)return;
		self::$__ic->__runstage++;
		self::$__ic->__instance=$__instance;
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		if(@method_exists($class,"_on_install"))
		{
			$res=@call_user_func(array($class,"_on_install"));
			if(!is_bool($res))$res=true;
			return $res;
		}
		return true;
	}

	final public static function __uninstall($instance)
	{
		self::$__isadmin=defined("ADMIN_MODE") && auth::admin();
		if(!self::_isAdmn())
		{
			msgr::add(_t("Can't uninstall from non-admin environment."));
			return false;
		}
		if(!self::_iGet($instance))return;
		if(self::$__ic->__runstage>0)return;
		self::$__ic->__runstage++;
		self::$__ic->__instance=$__instance;
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		if(@method_exists($class,"_on_uninstall"))
		{
			$res=@call_user_func(array($class,"_on_uninstall"));
			if(!is_bool($res))$res=true;
			return $res;
		}
		return true;
	}

	/**
	* Вызов функции рендеринга дочернего класса
	*
	* @param string $instance - имя класса, например FlexEngine\mymod
	* @param integer $sid - номер спота
	* @param string $method - функция рендеринга, по-умолчанию - __render
	*/
	final public static function __render($instance,$sid,$method="")
	{
		if(!self::_iGet($instance))return;
		//получаем все аргументы, и удаляем первый $instance
		//так как это системный аргумент
		$args=func_get_args();
		array_splice($args,0,1);
		//заново формируем $class, так как $instance может
		//содержать класс без указания namespace
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		//если существует пользовательский метод, то это означает
		//что он напрямую прикреплен к споту, поэтому
		//дополнительной удаляем аргументы $sid и $method
		if($method && @method_exists($class,$method))
		{
			array_splice($args,0,2);
			@call_user_func_array(array($class,$method),$args);
		}
		//в противном случае передаем $sid и $method в общую функцию рендеринга
		//$method при этом будет указывать на шаблон $tpl ($method === $tpl)
		elseif(@method_exists($class,"_on3render"))
		{
			//вырезаем системный префикс у названия шаблона
			if(isset($args[1]))$args[1]=str_replace("tpl:","",$args[1]);
			@call_user_func_array(array($class,"_on3render"),$args);
		}
	}

	final public static function __service($instance)
	{
		if(!self::_iGet($instance))return;
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		if(@method_exists($class,"_on0service"))return $class::_on0service();
		else return false;
	}

	final public static function __sleep($instance)
	{
		if(!self::_iGet($instance))return;
		if(self::$__ic->__runstage>2)return;
		self::$__ic->__runstage++;
		$class=__NAMESPACE__."\\".self::$__ic->__instance;
		if(@method_exists($class,"_on4sleep"))$class::_on4sleep();
		self::_sessionWrite();
	}
}
?>
