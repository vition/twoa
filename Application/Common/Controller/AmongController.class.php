<?php
/*作为中间层的类*/
namespace Common\Controller;
use Think\Controller;
class AmongController extends Controller {
	//初始化，类似构造方法，判断是否登录
	public function _initialize(){
		$oa_login=session("oa_islogin");
		if(empty($oa_login)){
			//防止死循环跳转
			if (strtoupper(CONTROLLER_NAME)!="INDEX") {
				$url=U("index/index");
				echo "<script>top.location.href='$url'</script>";exit;
			}
		}else{
			//获取到用户的信息
			$user=M("oa_user u");
			$userData=$user->field("user_username,user_name,user_code,r.role_upper user_roleg,c.config_value user_company,g.group_name user_group,p.place_name user_place,r.role_name user_role,user_director,user_phone,user_avatar,user_born,user_sex,user_lastlogin,user_entry,user_login,user_state")->where("user_username='".session("oa_user_username")."' AND  u.user_company=c.config_key AND u.user_group=g.group_id AND u.user_place=p.place_id AND u.user_role=r.role_id AND c.config_class='company' AND u.user_state=1")->join("oa_config c,oa_group g,oa_place p,oa_role r")->find();

			if(empty($userData["user_username"])){//防止用户离职后还使用
				session("oa_islogin",NULL);
    			session("oa_user_username",NULL);
			}else{
				if(strtoupper(MODULE_NAME)=="ADMIN" AND $userData["user_roleg"]!=1){
					$url=U("Home/Menu/menu");//防止普通员工进入admin
					echo "<script>top.location.href='$url'</script>";exit;
				}else{
					// if($this->authority()){
					$userData["user_age"]=get_age($userData["user_born"]);
					$userData["user_joinDay"]=get_day($userData["user_entry"]);
					$this->user=$userData;
					$this->assign("user",$userData);
					$this->assign("rand",lcg_value());
					// }else{
					// 	// echo "对不起，您没有权限！";
					// }
					
				}
				
			}
			
		}
	}
	//获取html页面
	public function gethtml(){
		if(IS_POST){
			if($this->authority()){
				$this->display(I("html"));
				return true;
			}
		}
		$this->show("<h1>对不起！您木有权限</h1>");
		return false;
	}
	//默认所有控制器index就是登录的页面，存在就跳转，不存在就登录
	public function index(){
    	$oa_login=session("oa_islogin");
		if(empty($oa_login)){
			$this->display("login");
		}else{
			$this->success("已登录",U("Menu/menu"));
		}
    	
    }
    //登录功能
    public function login(){
    	if(IS_POST){
    		$user=M("oa_user u");
    		$userData=$user->field("user_id,r.role_upper user_roleg")->where("u.user_username='".I("user_username")."' AND u.user_passwd='".sha1(I("user_passwd"))."' AND u.user_role=r.role_id AND u.user_state=1")->join("oa_role r")->find();

    		if($userData["user_id"]>0){
    			if(strtoupper(MODULE_NAME)=="ADMIN" AND $userData["user_roleg"]!=1){
    				$this->error("抱歉！非管理员无法登陆后台",U("index/index"),3);
    				return false;
    			}else{
    				session("oa_islogin","1");
	    			session("oa_user_username",I("user_username"));
	    			$this->success("登录成功",U("Menu/menu"));
	    			return true;
    			}
    		}
    	}
    	$this->error("登录失败",U("index/index"),1);
    	return false;
    }
    //退出
	public function logout(){
		if(IS_POST){
			session("oa_islogin",NULL);
    		session("oa_user_username",NULL);
    		echo "logout";
		}
	}
    //权限控制
    public function authority(){
    	$authority=$this->get_auth();
    		if(isset($authority[CONTROLLER_NAME])){
	    		foreach ($authority[CONTROLLER_NAME]["menus"] as $names => $menus) {
	    			if($this->menu_list($menus,I("html"))){
	    				return true;
	    			}
	    		}
	    		return false;
	    	}
    	
    	
    	// foreach ($authority as $key => $value) {
    	// 	$html=explode(",", $value);
    	// }
    	// $authority=explode(",", $this->get_auth());
    	// $user=M("oa_user");
    	// $userData=$user->field("user_role")->where("user_username='".session("oa_user_username")."'")->find();
    	// if($userData["user_role"]==2){
    	// 	return true;
    	// }
    	// if(ACTION_NAME=="gethtml"){//通过gethtml方法获取页面的权限名为：控制器+"-"+参数html
    	// 	$powerName=CONTROLLER_NAME."-".I("html");
    	// }else{//其他权限名为：控制器+"-"+方法名
    	// 	$powerName=CONTROLLER_NAME."-".ACTION_NAME;
    	// }
    	// echo $powerName;
    	// echo $authority;
    	// if(in_array($powerName, $authority)){
    	// 	return true;
    	// }else{
    	// 	return false;
    	// }
    }
    //获取权限
    public function get_auth($elimArray="",$module=true,$user_role=0){

    	
    	$rauth=M("oa_rauth a");
    	if($user_role>0){
    		$rauthData=json_decode($rauth->field("rauth_auth")->where("a.rauth_role='{$user_role}'")->find()["rauth_auth"],true);
    	}else{
    		$rauthData=json_decode($rauth->field("rauth_auth")->join("oa_user u")->where("u.user_username='".session("oa_user_username")."' AND u.user_role=a.rauth_role")->find()["rauth_auth"],true);
    	}
    	

    	if($module){
    		$rAtuhArray=$rauthData[MODULE_NAME];
    		if(!empty($elimArray)){
	    		foreach ($elimArray as $elim) {
	    			if(isset($rAtuhArray[$elim])){
	    				unset($rAtuhArray[$elim]);
	    			}
	    		}
	    	}
    	}else{
			$rAtuhArray=$rauthData;
    	}
    	return $rAtuhArray;
    }

    /**
     * [menu_list 循环判断权限]
     * @param  [type] $array [被判断的数组/值]
     * @param  [type] $menu  [要判断的html名]
     * @return [type]        [布尔值]
     */
    protected function menu_list($array,$menu){

    	if(is_array($array["menus"])){

    		foreach ($array["menus"] as $key => $value) {
    			return $this->menu_list($value,$menu);
    		}
    	}else{
    		if($menu==$array["menus"]){
    			return true;
    		}else{
    			return false;
    		}
    	}
    }

}