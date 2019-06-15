<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
class Alter extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('z_model','z');
		$this->load->model('report_model_z','reportz');
		$this->load->model('vhisSaso_model','s');
		$this->load->model('vhisSaso2_model','s2');
	}
	public function secToHR($seconds) {
		$hours = floor($seconds / 3600); $minutes = floor(($seconds / 60) % 60); $seconds = $seconds % 60;
		return str_pad($hours, 2, '0', STR_PAD_LEFT).":".str_pad($minutes, 2, '0', STR_PAD_LEFT).":".str_pad($seconds, 2, '0', STR_PAD_LEFT);
	}
	function mssql_escape($str){
		if(get_magic_quotes_gpc()) { $str= stripslashes($str); }
		return str_replace("'", "''", $str);
	}	
	public function output(){
		$this->load->view('header');
		$df = date("m/d/Y H:i:s ", strtotime($this->mssql_escape($this->input->post("datefrom"))) );
		$dt = date("m/d/Y H:i:s ", strtotime($this->mssql_escape($this->input->post("dateto"))) );
		$fq = $this->mssql_escape($this->input->post("frequency"));
		$aid = rtrim($this->mssql_escape($this->input->post("agentids")),',');
		$reportId = $this->mssql_escape($this->input->post("report_type"));
		if($aid==''){ $aid=$this->getAllAgents(); } 
		$sid = rtrim($this->mssql_escape($this->input->post("siteid")),',');
		if($sid==''){ $sid='MOC,BALINTAWAK,STA ROSA'; }
		$svd = $this->mssql_escape($this->input->post("serviceids"));
		if($svd==''){ $svd=$this->getAllServices(); }
		$skd = $this->mssql_escape($this->input->post("skillids"));
		if($skd==''){ $skd=$this->getAllSkills(); }
		switch($this->mssql_escape($this->input->post("report_type"))){
				case "VHIS 32":
				$z = $this->s2->get_vhis32_data($df,$dt,$sid,$skd,$svd);
				if($fq=="D" || $fq=="W" || $fq=="PM"|| $fq=="M" || $fq=="Y"){ $dt = strtotime($dt); $dt = date('Y-m-d h:i:s A',$dt); }
				$html = "";
				$data = array( 'z' => $z, 'df'=> $df, 'svd'=> $svd, 'dt'=> $dt, 'freq'=> $fq, 'html'=>$html);
				$this->load->view('template/RepTable 32',$data);
				break;
				case "VHIS 33":
				$z1 = $this->s2->get_vhis32_data($df,$dt,$sid,$skd,$svd);
				if($fq=="D" || $fq=="W" || $fq=="PM"|| $fq=="M" || $fq=="Y"){ $dt = strtotime($dt); $dt = date('Y-m-d h:i:s A',$dt); }
				$html = "";
				$this->load->model('vhisJohn_model','vhisJohn');
				if($this->input->post('report_type') == "") { echo "Invalid parameters or session expired."; die(); }
				$range = $this->input->post('frequency'); $agents 	= $this->input->post('agentids'); $siteid 	= $this->input->post('siteid'); $srvcid 	= $this->input->post('serviceids'); $skillid 	= $this->input->post('skillids'); $dtFrom =  $this->input->post('datefrom'); $dtTo =  $this->input->post('dateto');
				if(date('h:i:s A',strtotime($dtTo)) == "11:59:00 PM") { $dtTo = date('m/d/Y h:i:s A',strtotime('+59 second',strtotime($dtTo))); }
				if($skillid == "") { $skillid = $this->getAllSkills(); }
				if($siteid == ""){ $siteid = 'MOC,BALINTAWAK,STA ROSA'; }
				if($srvcid == "") { $srvcid=$this->getAllServices(); }
				$reportdata1 = $z1->result_array(); $reportdata2 = $z1->result_object(); $ctr=$this;
				$this->load->view('header');
				$data = array( 'result' => $reportdata1, 'z3' => $z1, 'z1' => $z1, 'z2' => $z1, 'df'=> $df, 'svd'=> $svd, 'dt'=> $dt, 'ctr'=>$ctr, 'range'=>$range, 'freq'=> $fq, 'html'=>$html);
				$this->load->view('john/Report_vhis33alter',$data);
				break;		
		}			
	}
	public function getAllAgents(){ $x = $this->z->getAgents(); $html = ""; foreach($x->result_object() as $row){ $html .= "$row->User_Id,"; } return $html; }
	public function getAllTrunk(){ $x = $this->z->getTrunks(); $html = ""; foreach($x->result_object() as $row){ $html .= "$row->TrunkId,"; } return $html; }
	public function getAllSkills(){ $x = $this->z->getSkillsets(); $html = ""; foreach($x->result_object() as $row){ $html .= "$row->Skill_Id,"; } return $html; }
	public function getAllServices(){ $x = $this->z->getServices(); $html = ""; foreach($x->result_object() as $row){ $html .= "$row->Service_Id,"; } return $html; }
	public function getAllAttendants(){ $x = $this->z->getAttendants(); $html = ""; foreach($x->result_object() as $row){ $html .= "$row->Agent,"; } return $html; }
	public function getAllTelex(){ $x = $this->z->getTelex(); $html = ""; foreach($x->result_object() as $row){ $html .= "$row->TelexServiceId,"; } return $html; }

}
