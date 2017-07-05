<?php
/**
 * @Author: vition
 * @Date:   2017-05-18 15:57:50
 * @Last Modified by:   vition
 * @Last Modified time: 2017-07-05 18:43:50
 */
/*用户功能{list|用户列表,create|新建用户,edit|编辑用户,ubase|基础信息}*/
namespace Home\Controller;
use Common\Controller\AmangController;
class UserController extends AmangController {
	//重组gethtml方法
	public function gethtml(){
		switch (I("html")) {
			case 'list':
				$user=M("oa_user u");
				$userData=$user->field("user_name,user_code,c.config_value user_company,g.group_name user_group,p.place_name user_place,r.role_name user_role,user_director,user_phone,user_avatar,user_born,user_sex,user_lastlogin,user_entry,user_login,user_state")->where("u.user_company=c.config_key AND u.user_group=g.group_id AND u.user_place=p.place_id AND u.user_role=r.role_id AND c.config_class='company'")->join("oa_config c,oa_group g,oa_place p,oa_role r")->select();
				$this->assign("userlist",$userData);
				break;
			case 'create': case 'ubase':
				$user=M("oa_user u");
				$userData=$user->field("user_code")->order("user_code DESC")->find();
				$this->assign("user_code",$userData["user_code"]+1);
				$config=M("oa_config");
				$companyData=$config->field("config_key,config_value")->where("config_class='company'")->select();
				$this->assign("user_companys",$companyData);

				$group=M("oa_group g");
				$groupData=$group->field("group_id,group_name")->select();
				$this->assign("user_groups",$groupData);

				$place=M("oa_place");
				$placeData=$place->field("place_id,place_name")->select();
				$this->assign("user_place",$placeData);

				$role=M("oa_role");
				$roleData=$role->field("role_id,role_name")->where("role_upper=0")->order("role_id ASC")->select();
				$this->assign("role_group",$roleData);
				break;
			case 'edit':
				# code...
				break;
			case 'ubases':
				$config=M("oa_config");
				$companyData=$config->field("config_key,config_value")->where("config_class='company'")->select();
				$this->assign("user_companys",$companyData);
				break;
			default:
				break;
		}
		parent::gethtml();
	}
	//新建用户
	public function create(){
		if(IS_POST){
			$user=M("oa_user");
			$userData=$_POST;
			
			if(empty($userData["user_passwd"])){
				$userData["user_passwd"]=sha1("Aa1234567");//初始化密码
			}else{
				$userData["user_passwd"]=sha1($userData["user_passwd"]);//密码加密
			}
			if($userData["user_sex"]=="男"){
				$userData["user_avatar"]="/assets/avatars/man.png";
			}else if($userData["user_sex"]=="女"){
				$userData["user_avatar"]="/assets/avatars/lady.png";
			}	
			$userData["user_quit"]="0000-00-00";
			$result=$user->add($userData);
			echo $result;
		}
	} 
	//查看下级信息
	public function showlowe(){
		if(IS_POST){
			switch ($_POST['type']) {
				case 'place':
					$group=D("Group");
					$subgroupData=$group->select_subgroup($_POST["id"]);
					$palceHtml="";
					if(isset($_POST["group"])){
						$placeData=$group->select_place($_POST["group"],$_POST["id"]);	
					}else{
						$placeData=$group->select_place($_POST["id"]);	
					}
					
					foreach ($placeData as $placeArray) {
						$palceHtml.="<option class='ubase-select' data-input='place-data' data-manager='{$placeArray["place_manager"]}' value='{$placeArray["place_id"]}'>{$placeArray["place_name"]}</option>";
					}
					if(!isset($_POST["sub"])){
						$sgHtml="";
						foreach ($subgroupData as $subgroup) {
							$sgHtml.="<option class='ubase-select' data-type='place' data-sub='true' data-input='subgroup-data2' value='{$subgroup["subgroup_id"]}'>{$subgroup["subgroup_name"]}</option>";
						}
						echo json_encode(array("subgroup"=>$sgHtml,"place"=>$palceHtml));
					}else{
						echo json_encode(array("place"=>$palceHtml));
					}
					
					
					// print_r($_POST);
					
					break;	
				case 'subgroup':
					$subgroup=M('oa_subgroup');
					$subgroupData=$subgroup->field("subgroup_id,subgroup_name")->where("subgroup_group='{$_POST["id"]}'")->select();
					//print_r($placeData);
					$html="";
					foreach ($subgroupData as $subgroupArray) {
						$html.="<option class='ubase-select' data-input='subgroup-data' value='{$subgroupArray["subgroup_id"]}'>{$subgroupArray["subgroup_name"]}</option>";
					}
					echo $html;
					break;	
				case 'role':
					// print_r($_POST);
					$role=D("Group");
					$roleHtml="";
					$roleData=$role->select_role($_POST["id"]);
					foreach ($roleData as $roleArray) {
						$roleHtml.="<option class='ubase-select' data-input='role-data' value='{$roleArray["role_id"]}'>{$roleArray["role_name"]}</option>";
					}
					echo $roleHtml;
					break;
				default:
					# code...
					break;
			}
		}
	}
	//添加信息
	public function addinfo(){
		if(IS_POST){
			switch ($_POST['type']) {
				case "company":
					$company=D("Company");
					$data=$company->add_company($_POST['value']);
					if($data>0){
						$newC=$company->find_company(0,$_POST['value']);
						$jsonData=array("msg"=>"success","option"=>"<option class='ubase-select' data-input='company-data' value='{$newC["config_key"]}'>{$newC["config_value"]}</option>");
					}else{
						$jsonData=array("msg"=>$data);
					}
					echo json_encode($jsonData);
				break;
				case "group":
					$group=D("Group");
					$groupData=$group->add_group($_POST['value']);
					if($groupData>0){
						$newG=$group->find_group(0,$_POST['value']);
						$jsonData=array("msg"=>"success","option"=>"<option class='ubase-select' data-input='groups-data' data-type='subgroup' value='{$newG["group_id"]}'>{$newG["group_name"]}</option>");
					}else{
						$jsonData=array("msg"=>$groupData);
					}
					echo json_encode($jsonData);
				break;
				case "subgroup":
				$subgroup=D("Group");
				$subgroupData=$subgroup->add_subgroup($_POST['group'],$_POST['value']);	
				if($subgroupData>0){
					$newS=$subgroup->find_subgroup($subgroupData);
					$jsonData=array("msg"=>"success","option"=>"<option class='ubase-select' data-input='subgroup-data' value='{$newS["subgroup_id"]}'>{$newS["subgroup_name"]}</option>");
				}else{
					$jsonData=array("msg"=>$subgroupData);
				}
					echo json_encode($jsonData);
				break;
				case "place":
				// echo print_r($_POST);
					$palce=D("Group");
					if(isset($_POST["subgroup"])){
						$addResult=$palce->add_place($_POST["group"],$_POST["value"],$_POST["manager"],$_POST["subgroup"]);

					}else{
						$addResult=$palce->add_place($_POST["group"],$_POST["value"],$_POST["manager"]);
					}
					if($addResult>0){
						$placeData=$palce->find_place($addResult);
						$jsonData=array("msg"=>"success","option"=>"<option class='ubase-select' data-input='place-data' data-manager='{$placeData["place_manager"]}' value='{$placeData["place_id"]}'>{$placeData["place_name"]}</option>");
					}else{
						$jsonData=array("msg"=>$addResult);
					}

				// $jsonData=array("msg"=>print_r($_POST));
					echo json_encode($jsonData);
				break;
				case "role": case "subrole":
					// print_r($_POST);
					if($_POST['type']=="role"){
						$input='role-group-data';
						$type="data-type='role-data'";
					}else{
						$type='';
						$input='role-data';
					}
					$role=D("Group");
					$role_upper=isset($_POST["group"])?$_POST["group"]:0;
					$addResult=$role->add_role($_POST["value"],$role_upper);
					if($addResult>0){
						$roleData=$role->is_role($_POST["value"],$role_upper);
						$jsonData=array("msg"=>"success","option"=>"<option class='ubase-select' {$type} data-input='{$input}' value='{$roleData["role_id"]}'>{$roleData["role_name"]}</option>");
					}else{
						$jsonData=array("msg"=>$addResult);
					}
					echo json_encode($jsonData);
				break;

			}
		}
	}
	//更新信息
	public function updateinfo(){
		if(IS_POST){
			switch ($_POST['type']) {
				case "company":
					$company=D("Company");
					$resultData=$company->set_company($_POST["company_key"],$_POST["company_name"]);
					if($resultData>0){
						echo "success";
					}else{
						echo $resultData;
					}
				break;
				case "group": case "subgroup":
					$group=D("Group");
					if($_POST["type"]=="group"){
						$resultData=$group->set_group($_POST["key"],$_POST["value"]);
					}else{
						$resultData=$group->set_subgroup($_POST["key"],$_POST["group"],$_POST["value"]);
					}
					if($resultData>0){
						echo "success";
					}else{
						echo $resultData;
					}
				break;
				case "place":
					$place=D("Group");

					$subgroup=isset($_POST["subgroup"])?$_POST["subgroup"]:0;
					$resultData = $place->set_place($_POST["key"],$_POST["group"],$_POST["value"],$_POST["manager"],$subgroup);
					if($resultData>0){
						echo "success";
					}else{
						echo $resultData;
					}
				break;
				case "role": case "subrole":
					$role=D("Group");
					$role_upper=isset($_POST["group"])?$_POST["group"]:0;
					$resultData=$role->set_role($_POST["key"],$_POST["value"],$role_upper);
					if($resultData>0){
						echo "success";
					}else{
						echo $resultData;
					}
				break;
			}
		}
	}
	// 删除信息
	public function delinfo(){
		if(IS_POST){
			switch ($_POST['type']) {
				case "company":
					$company=D("Company");
					$delResult=$company->del_company($_POST["company_key"]);
					echo "success";

				break;
				case "group": case "subgroup":
					$group=D("Group");
					if($_POST["type"]=="group"){
						$delResult= $group->del_group($_POST["key"]);
					}else{
						$delResult= $group->del_subgroup($_POST["key"]);
					}
					echo "success";
				break;
				case "place":
					$place=D("Group");
					$delResult= $place->del_place($_POST["key"]);
					echo "success";
				break;

				break;
				case "role": case "subrole":
					$role=D("Group");
					if ($_POST["type"]=="role"){
						if ($role->select_role($_POST["key"])!="") {
							echo "该分组下含有其他角色，请删除角色再删除";
							return false;
						}
					}
					echo $role->del_role($_POST["key"]);
				break;
			}
		}
	}
}