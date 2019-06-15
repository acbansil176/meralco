<?php defined('BASEPATH') OR exit('No direct script access allowed');
ini_set('mssql.timeout', 60 * 10);ini_set('sqlsrv.timeout', 60 * 10);ini_set('memory_limit', '4056M');ini_set('upload_max_filesize', '1000M');ini_set('post_max_size', '1000M');

class Report_controller_z extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model('z_model','z');
		$this->load->model('report_model_z','reportz');
		$this->load->model('vhisSaso_model','s');
		$this->load->model('vhisSaso2_model','s2');
	}
	public function secToHR($seconds) {
		$hours = floor($seconds / 3600);$minutes = floor(($seconds / 60) % 60);$seconds = $seconds % 60;
		return str_pad($hours, 2, '0', STR_PAD_LEFT).":".str_pad($minutes, 2, '0', STR_PAD_LEFT).":".str_pad($seconds, 2, '0', STR_PAD_LEFT);
	}
	public function format_period($seconds_input) {
		$hours=(int)($minutes = (int)($seconds=(int)($milliseconds=(int)($seconds_input * 1000)) /1000 )/60 ) /60;
		return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' .str_pad(($minutes%60), 2, '0', STR_PAD_LEFT) .':'.str_pad(($seconds%60), 2, '0', STR_PAD_LEFT). (($milliseconds===0)? '.0' : '.' .rtrim($milliseconds%1000,'0'));	
	}
	public function secToHR2($seconds) {
		$milliseconds=substr(/*z*/ number_format($seconds,2),-2);$hours = floor($seconds / 3600);$minutes = floor(($seconds / 60) % 60);$seconds = $seconds % 60;
		return str_pad($hours, 2, '0', STR_PAD_LEFT).":".str_pad($minutes, 2, '0', STR_PAD_LEFT).":".str_pad($seconds, 2, '0', STR_PAD_LEFT).'.'.$milliseconds;
	}
	public function HRToSec($time) {
		$sec = 0;$hms = explode(":",$time);	$sec = $sec + ($hms[0] * 360);$sec = $sec + ($hms[1] * 60);$sec = $sec + ($hms[2]); return $sec;
	}
	public function index() {
		$this->load->view("header");$this->load->view("template/report_tester");
	}
	
	function mssql_escape($str){
		if(get_magic_quotes_gpc()){$str= stripslashes($str);}
		return str_replace("'", "''", $str);
	}	
	
	public function output(){
		if($this->uri->segment(3) == "") {
			$this->session->unset_userdata('report_type');
			$this->session->unset_userdata('frequency');
			$this->session->unset_userdata('df');
			$this->session->unset_userdata('dt');
			$this->session->unset_userdata('tblname');
		}
		$this->load->view('header');
		$df = date("m/d/Y H:i:s ", strtotime($this->mssql_escape($this->input->post("datefrom"))) );
		$dt = date("m/d/Y H:i:s", strtotime($this->mssql_escape($this->input->post("dateto"))) );
		$df2 = date("m/d/Y h:i:s a", strtotime($this->mssql_escape($this->input->post("datefrom"))) );
		$dt2 = date("m/d/Y h:i:s a", strtotime($this->mssql_escape($this->input->post("dateto"))) );
		$fq = $this->mssql_escape($this->input->post("frequency"));
		$aid = rtrim($this->mssql_escape($this->input->post("agentids")),',');
		$aid = $this->mssql_escape($this->input->post("agentids"));
		$srv = rtrim($this->mssql_escape($this->input->post("serviceids")),",");
		$htl = rtrim($this->mssql_escape($this->input->post("myhotlines")),",");
		$tid= $this->mssql_escape($this->input->post("trunkids"));
		$atd= $this->mssql_escape($this->input->post("attendants"));
		$tlx= $this->mssql_escape($this->input->post("telex"));
		$sid = rtrim($this->mssql_escape($this->input->post("siteid")),',');
		$svd = $this->mssql_escape($this->input->post("serviceids"));
		$skd = str_replace('undefined,', '',$this->mssql_escape($this->input->post('skillids')));
		if($aid==''){ $aid=$this->getAllAgents(); }
		if($tid==''){ $tid=$this->getAllTrunk(); }
		if($atd==''){ $atd=$this->getAllAttendants(); }
		if($tlx==''){ $tlx=$this->getAllTelex(); }
		if($htl==''){ $htl='16211';}
		if($sid==''){ $sid='MOC,BALINTAWAK,STA ROSA'; }
		if($svd==''){ $svd=$this->getAllServices();	}
		if($skd==''){ $skd=$this->getAllSkills(); }
		if($this->input->post('report_type') != "") { $rptype = $this->input->post('report_type'); }
		elseif($this->session->userdata('report_type') != "") { $rptype = $this->session->userdata('report_type'); }
		else { die('Invalid report type.'); }
		switch($this->mssql_escape($rptype)){
			case "VHIS 1A":
				if($fq == "Morning") {
					$z = $this->z->getVis01AShift($df,$dt,$sid,$aid,"6AM - 2PM");
					$data = array('z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt);
					$this->load->view('zetto/RepTable 1A_M',$data);
				} elseif($fq == "Afternoon") {
					$z = $this->z->getVis01AShift($df,$dt,$sid,$aid,"2PM - 10PM");
					$data = array('z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt);
					$this->load->view('zetto/RepTable 1A_E',$data);
				} elseif($fq == "Graveyard") {
					$z = $this->z->getVis01AShift($df,$dt,$sid,$aid,"10PM - 6AM");
					$data = array('z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt);
					$this->load->view('zetto/RepTable 1A_G',$data);
				} else {
					$z = $this->z->getVis01A($df,$dt,$sid,$aid);
					$data = array('z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt);
					$this->load->view('zetto/RepTable 1A',$data);
				}
			break;
			case "VHIS 1B":
				$z= $this->z->getVis01B($df,$dt,$sid,$aid);
				$data = array('z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt);
				$this->load->view('zetto/RepTable 1B',$data);
			break;
			case "VHIS 2A":	
				$z = $this->z->getVis02Bx($df,$dt,$aid,$svd,$sid,$fq);
				if($fq=="D" || $fq=="W" || $fq=="PM"|| $fq=="M" || $fq=="Y"){	
					$dt = strtotime($dt);$dt = date('Y-m-d h:i:s A',$dt);		
				}
				$data = array('z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt,'freq'=> $fq);
				$this->load->view('zetto/RepTable 2AMark',$data);
			break;		
			case "VHIS 2B":
				$z = $this->z->getVis02Bx($df,$dt,$aid,$svd,$sid,$fq);
				if($fq=="D" || $fq=="W" || $fq=="PM"|| $fq=="M" || $fq=="Y"){	
					$dt = strtotime($dt);$dt = date('Y-m-d h:i:s A',$dt);
				}
				$data = array('z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt,'freq'=> $fq);
				$this->load->view('zetto/RepTable 2B',$data);
			break;	
			case "VHIS 03":
				$z = $this->z->getVis03($fq,$df,$dt,$skd,$sid,$aid,$svd);
				$data = array('freq'=>$fq,'z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt);
				$this->load->view('zetto/RepTable 03',$data);
			break;
			case "VHIS 04":
				$this->load->library('pagination');$z = "";
				if($this->session->userdata('report_type') == "") { $z =$this->reportz->get_report_4($df,$dt); }
				$data = array('z' => $z,'df'=> $df,'dt'=> $dt,'freq'=> $fq);
				$this->load->view('template/RepTable 042',$data);

			break;
			case "VHIS 05A":
				$this->load->library('pagination');$z = "";
				if($this->session->userdata('report_type') == "") { $z = $this->z->getVis05AGenTable($df,$dt,$skd,$sid,$aid,$svd,$sid,$svd,$skd); }
				$data = array('freq'=>$fq,'z'=>$z,'df'=>$df,'svd'=>$svd,'dt'=>$dt,'ctrl'=>$this);
				$this->load->view('zetto/RepTable 5A2',$data);
			break;
			case "VHIS 05B":
				$this->load->library('pagination');$z = "";
				if($this->session->userdata('report_type') == "") { $z = $this->z->getVis05AGenTable($df,$dt,$skd,$sid,$aid,$svd,$sid,$svd,$skd); }
				$data = array('freq'=>$fq,'z'=>$z,'df'=>$df,'svd'=>$svd,'dt'=>$dt,'ctrl'=>$this);
				$this->load->view('zetto/RepTable 5B2',$data);
			break;
			case "VHIS 6A":
				$z = $this->s->get_vhis6a_data($df,$dt,$sid,$tid,$fq);
				$data = array('z' => $z,'df'=> $df,'dt'=> $dt,'freq'=> $fq);
				$this->load->view('template/RepTable 6A',$data);
			break;
			case "VHIS 6B":
				$dtTo = $this->mssql_escape($this->input->post('dateto'));
				if(date('h:i:s A',strtotime($dtTo)) == "11:59:00 PM") {$dtTo = date('m/d/Y h:i:s A',strtotime('+59 second',strtotime($dtTo)));}
				$z = $this->s->vhis_6B($df,$dtTo,$tid);
				$data = array('z' => $z,'df'=> $df,'dt'=> $dtTo,'freq'=> $fq);
				$this->load->view('template/RepTable 6B',$data);
			break;
			case "VHIS 07":
				$reportdata = $this->reportz->get_report_07($df,$dt,$sid);
				$data = array('reportdata' => $reportdata,'df'=> $df,'dt'=> $dt,'frequency'=> $fq);
				$this->load->view('template/RepTable 07',$data);
			break;
			case "VHIS 08":
				$this->load->model('mojomodel','mojo');
				$freq = $this->mssql_escape($this->input->post("frequency"));
				$datefrom = $this->mssql_escape($this->input->post("datefrom"));
				$dateto = $this->mssql_escape($this->input->post("dateto"));
				$result = $this->mojo->vhis_8($datefrom, $dateto,$sid);
				$data = array('frequency' => $freq,'datefrom' => $df,'dateto' => $dt,'records' => $result);
				$this->load->view('mojo/vhis_08', $data);
			break;		
			case "VHIS 09":
				$this->load->model('mojomodel','mojo');
				$freq = $this->mssql_escape($this->input->post("frequency"));
				$datefrom = $this->mssql_escape($this->input->post("datefrom"));
				$dateto = $this->mssql_escape($this->input->post("dateto"));
				$result = $this->mojo->vhis_9($datefrom, $dateto,$sid);
				$data = array('frequency' => $freq,'datefrom' => $df,'dateto' => $dt,'records' => $result,'ctr'=>$this);
				$this->load->view('mojo/vhis_09', $data);
			break;
			case "VHIS 3":
				$z = $this->z->getVis03($fq,$df,$dt,$skd,$sid,$aid,$svd);
				$data = array('freq'=>$fq,'z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt);
				$this->load->view('zetto/RepTable 03',$data);
			break;
			case "VHIS 4":
				$reportdata = $this->reportz->get_report_4($df,$dt);
				if(date('h:i:s A',strtotime($dt)) == "11:59:00 PM") {$dt = date('m/d/Y H:i:s',strtotime('+59 second',strtotime($dt)));}
				$data = array('reportdata' => $reportdata,'df'=> $df,'dt'=> $dt,'freq'=> $fq);
				$this->load->view('template/RepTable 04',$data);
			break;
			case "VHIS 5A":
				$dfx = date("Y-m-d H:i", strtotime("-15 minutes", strtotime($df)));
				$z = $this->z->getVis05A($df,$dt,$skd,$sid,$aid,$svd,$sid,$svd,$skd);
				$data = array('freq'=>$fq,'z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt);
				$this->load->view('zetto/RepTable 5A',$data);
			break;
			case "VHIS 5B":
				$z = $this->z->getVis05A($df,$dt,$skd,$sid,$aid,$svd,$sid,$svd,$skd);
				$data = array('freq'=>$fq,'z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt);
				$this->load->view('zetto/RepTable 5B2',$data);
			break;
			case "VHIS 7":
				$reportdata = $this->reportz->get_report_07($df,$dt,$sid);
				$data = array('reportdata' => $reportdata,'df'=> $df,'dt'=> $dt,'frequency'=> $fq);
				$this->load->view('template/RepTable 07',$data);
			break;
			case "VHIS 8":
				$this->load->model('mojomodel','mojo');
				$freq = $this->mssql_escape($this->input->post("frequency"));
				$datefrom = $this->mssql_escape($this->input->post("datefrom"));
				$dateto = $this->mssql_escape($this->input->post("dateto"));
				$result = $this->mojo->vhis_8($datefrom, $dateto,$sid);
				$data = array('frequency' => $freq,'datefrom' => $df,'dateto' => $dt,'records' => $result);
				$this->load->view('mojo/vhis_08', $data);
			break;		
			case "VHIS 9":
				$this->load->model('mojomodel','mojo');
				$freq = $this->mssql_escape($this->input->post("frequency"));
				$datefrom = $this->mssql_escape($this->input->post("datefrom"));
				$dateto = $this->mssql_escape($this->input->post("dateto"));
				$result = $this->mojo->vhis_9($datefrom, $dateto,$sid);
				$data = array('frequency' => $freq,'datefrom' => $df,'dateto' => $dt,'records' => $result,'ctr'=>$this);
				$this->load->view('mojo/vhis_09', $data);
			break;
			case "VHIS 10":
				$this->load->model('mojomodel','mojo');
				$freq = $this->mssql_escape($this->input->post("frequency"));
				$datefrom = $this->mssql_escape($this->input->post("datefrom"));
				$dateto = $this->mssql_escape($this->input->post("dateto"));
				$result = $this->mojo->vhis_10($datefrom, $dateto ,$sid);
				$data = array('frequency' => $freq,'datefrom' => $df,'dateto' => $dt,'records' => $result);
				$this->load->view('mojo/vhis_10', $data);
			break;
			case "VHIS 11":
				$z = $this->s->get_vhis11_data($df,$dt,$sid);
				if($fq=="D" || $fq=="W" || $fq=="PM"|| $fq=="M" || $fq=="Y"){	
					$dt = strtotime($dt);$dt = date('Y-m-d h:i:s A',$dt);
				}
				$html = "";
				$data = array('z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt,'freq'=> $fq,'html'=>$html);
				$this->load->view('zetto/RepTable 11',$data);
			break;
			case "VHIS 12":
				$reportdata = $this->reportz->get_report_12($df,$dt,$sid,$svd,$skd);
				$data = array('reportdata' => $reportdata,'df'=> $df,'dt'=> $dt,'frequency'=> $fq);
				$this->load->view('template/RepTable 12',$data);
			break;
			case "VHIS 13":
				$reportdata = $this->reportz->get_report_13($df,$dt,$skd,$svd,$sid);
				$data = array('reportdata' => $reportdata,'df'=> $df,'dt'=> $dt,'frequency'=> $fq);
				$this->load->view('template/RepTable 13',$data);
			break;	
			case "VHIS 14":
				$dtTo = $this->mssql_escape($this->input->post('dateto'));
				if(date('h:i:s A',strtotime($dtTo)) == "11:59:00 PM") {$dtTo = date('m/d/Y h:i:s A',strtotime('+59 second',strtotime($dtTo)));}
				$reportdata = $this->reportz->get_report_14($df,$dtTo);
				$data = array('reportdata' => $reportdata,'df'=> $df,'dt'=> $dtTo,'frequency'=> $fq);
				$this->load->view('template/RepTable 14',$data);
			break;
			case "VHIS 15":
				$this->load->model('vhisJohn_model','vhisJohn');
				$range = $this->mssql_escape($this->input->post('frequency'));
				$dtFrom = $this->mssql_escape($this->input->post('datefrom'));
				$dtTo = $this->mssql_escape($this->input->post('dateto'));
				if(date('h:i:s A',strtotime($dtTo)) == "11:59:00 PM") {$dtTo = date('m/d/Y h:i:s A',strtotime('+59 second',strtotime($dtTo)));}
				$agents = "";$service = "";
				$result = $this->vhisJohn->get_vhis15_data($range,$dtFrom,$dtTo,$sid,$agents,$service, $tid);
				$data['result'] = $result->result();$data['df'] = date('m/d/Y H:i:s',strtotime($dtFrom));
				$data['dt'] = date('m/d/Y H:i:s',strtotime($dtTo));
				$this->load->view('john/Report_vhis15',$data);	
			break;
			case "VHIS 16":
				$z = $this->s->vhis_16($df,$dt,$htl);
				$data = array('z' => $z,'df'=> $df,'dt'=> $dt,'freq'=> $fq,'htl' => $htl);
				$this->load->view('template/RepTable 16',$data);
			break;
			case "VHIS 17":
				$reportdata = $this->reportz->get_report_17($df,$dt);
				$data = array('reportdata' => $reportdata,'df'=> $df,'dt'=> $dt,'freq'=> $fq);
				$this->load->view('template/RepTable 17',$data);
			break;	
			case "VHIS 18":
				$z = $this->z->getVis18($df,$dt,$skd,$sid,$aid,$svd,"","","");
				if($fq != "Morning" || $fq != "Afternoon" || $fq != "Graveyard") {
					if(date('h:i:s A',strtotime($dt)) == "11:59:00 PM") {$dt = date('m/d/Y h:i:s A',strtotime('+59 second',strtotime($dt)));}
				}	
				$data = array('z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt);
				$this->load->view('zetto/RepTable 18',$data);
			break;
			case "VHIS 19":
				$this->load->model('vhisJohn_model','vhisJohn');
				$range = $this->mssql_escape($this->input->post('frequency'));
				$dtFrom = $this->mssql_escape($this->input->post('datefrom'));
				$dtTo = $this->mssql_escape($this->input->post('dateto'));
				if(date('h:i:s A',strtotime($dtTo)) == "11:59:00 PM") {$dtTo = date('m/d/Y h:i:s A',strtotime('+59 second',strtotime($dtTo)));}
				$agents = "";$service = "";
				$result = $this->vhisJohn->get_vhis19_data($range,$dtFrom,$dtTo,$sid,$skd,$svd);
				$result2 = $this->vhisJohn->get_vhis19_ave_num($range,$dtFrom,$dtTo,$sid,$skd,$svd);
				$result3 = $this->vhisJohn->get_vhis19_ivrs_off($range,$dtFrom,$dtTo,$sid,$skd,$svd);
				$result4 = $this->vhisJohn->get_vhis19_ivrs_off($range,$dtFrom,$dtTo,$sid,$skd,$svd);
				$data['result'] = $result->result();$data['result2'] = $result2->result();$data['result3'] = $result3->result();
				$data['result4'] = $result4->result();$data['df'] = date('m/d/Y H:i:s',strtotime($dtFrom));
				$data['dt'] = date('m/d/Y H:i:s',strtotime($dtTo));$data['ctr']=$this;
				$this->load->view('john/Report_vhis19',$data);	
			break;
			case "VHIS 20":
				
				$this->load->library('pagination');$z = "";
				if($this->session->userdata('report_type') == "") { $z =$this->reportz->get_report_20($df,$dt); }
				$data = array('z' => $z,'df'=> $df,'dt'=> $dt,'freq'=> $fq);
				$this->load->view('john/Report_vhis202',$data);	
				
				
			break;
			case "VHIS 21":
				echo "<hr>";
				$reportdata = $this->reportz->get_report_21a($df,$dt,$tlx,$atd);
				$reportdata2 = $this->reportz->get_report_21b($df,$dt,$tlx,$atd);
				$data = array('reportdata' => $reportdata,'reportdata2' => $reportdata2,'df'=> $df,'dt'=> $dt,'frequency'=> $fq,'freq' => $fq);
				$this->load->view('template/RepTable 21',$data);
			break;
			case "VHIS 22":
				$z = $this->s->vhis_22($df,$dt,$atd,$tlx);
				$data = array('z' => $z,'df'=> $df,'dt'=> $dt,'freq'=> $fq);
				$this->load->view('template/RepTable 22',$data);
			break;
			case "VHIS 23":
				$z = $this->s->vhis_23($df,$dt,$atd,$tlx);
				$data = array('z' => $z,'df'=> $df,'dt'=> $dt,'freq'=> $fq);
				$this->load->view('template/RepTable 23',$data);
			break;
			case "VHIS 24":
				$reportdata = $this->reportz->get_report_24($df,$dt,$sid);
				if(date('h:i:s A',strtotime($dt)) == "11:59:00 PM") {$dt = date('m/d/Y H:i:s',strtotime('+59 second',strtotime($dt)));}
				$data = array('reportdata' => $reportdata,'df'=> $df,'dt'=> $dt,'frequency'=> $fq);
				$this->load->view('template/RepTable 24',$data);
			break;
			case "VHIS 25":
				$this->load->model('vhisJohn_model','vhisJohn');
				$range = $this->mssql_escape($this->input->post('frequency'));
				$dtFrom = $this->mssql_escape($this->input->post('datefrom'));
				$dtTo = $this->mssql_escape($this->input->post('dateto'));
				if(date('h:i:s A',strtotime($dtTo)) == "11:59:00 PM") {$dtTo = date('m/d/Y h:i:s A',strtotime('+59 second',strtotime($dtTo)));}
				$agents = "";$service = "";
				$result = $this->vhisJohn->get_vhis25_data($range,$dtFrom,$dtTo,$sid,$agents,$service);
				$data['result'] = $result->result();
				$data['df'] = date('m/d/Y h:i:s A',strtotime($dtFrom));
				$data['dt'] = date('m/d/Y h:i:s A',strtotime($dtTo));
				$this->load->view('john/Report_vhis25',$data);	
			break;
			case "VHIS 26":
				$this->load->model('vhisJohn_model','vhisJohn');
				$range = $this->mssql_escape($this->input->post('frequency'));
				$dtFrom = $this->mssql_escape($this->input->post('datefrom'));
				$dtTo = $this->mssql_escape($this->input->post('dateto'));
				if(date('h:i:s A',strtotime($dtTo)) == "11:59:00 PM") {$dtTo = date('m/d/Y h:i:s A',strtotime('+59 second',strtotime($dtTo)));}
				$agents = "";$service = "";
				$result = $this->vhisJohn->get_vhis26_data($range,$dtFrom,$dtTo,$sid,$agents,$service);
				$data['result'] = $result->result();
				$data['df'] = date('m/d/Y h:i:s A',strtotime($dtFrom));
				$data['dt'] = date('m/d/Y h:i:s A',strtotime($dtTo));
				$this->load->view('john/Report_vhis26',$data);	
			break;
			case "VHIS 27A":
				$df = date("Y-m-d h:i:s a", strtotime($this->mssql_escape($this->input->post("datefrom"))) );
				$dtx = date("Y-m-d h:i:s a", strtotime($this->mssql_escape($this->input->post("dateto"))) );
				$dtx = strtotime($dtx);
				$dt = date("Y-m-d h:i:s a", $dtx);
				$z = $this->z->getVis27ASummary($df,$dt,$sid,$svd);
				$zz = $this->z->getVis27APhoneBreakDown($df,$dt,$sid,$svd);
				$zzz = $this->z->getVis27ASocialMediaBreakDown($df,$dt,$sid,$svd);
				$mail = $this->z->getVis27Emailbreakdown($df,$dt,$sid,$svd);
				$data = array('z' => $z,'zz' => $zz,'zzz' => $zzz,'mmm' => $mail,'df'=> $df,'svd'=> $svd,'dt'=> $dt);
				$this->load->view('zetto/RepTable 27A',$data);
			break;		
			case "VHIS 27B":
				$df = date("Y-m-d h:i:s a", strtotime($this->mssql_escape($this->input->post("datefrom"))) );
				$dtx = date("Y-m-d h:i:s a", strtotime($this->mssql_escape($this->input->post("dateto"))) );
				$dtx = strtotime($dtx);
				$dt = date("Y-m-d h:i:s a", $dtx);
				$adata['xcnta1']= $this->z->dAgent($df,$dt,'moc',$svd);
				$adata['xcnta2']= $this->z->dAgent($df,$dt,'balintawak',$svd);
				$adata['xcnta3']= $this->z->dAgent($df,$dt,'sta rosa',$svd);
				$z = $this->z->getVis27BTotalCallCenterTransPhone($df,$dt,$sid,$svd);
				$zz = $this->z-> getVis27BTotalCallCenterTransSocialMedia($df,$dt,$sid,$svd);
				$zzz = $this->z-> getVis27BTotalCallCenterTransEmail($df,$dt,$sid,$svd);
				$zzzz = $this->z-> getVis27BTotalCallCenterTransTrunks($df,$dt,$sid,$svd);
				$zzzzz = $this->z-> getVis27BTotalCallCenterTransSM($df,$dt,$sid);
				$data = array('z' => $z,'zz' => $zz,'zzz' => $zzz,'zzzz' =>$zzzz,'zzzzz' =>$zzzzz,'df'=> $df,'svd'=> $svd,'dt'=> $dt,'dAgent'=> $adata);
				$this->load->view('zetto/RepTable 27B',$data);
			break;
			case "VHIS 28":
				$this->load->model('vhisSaso2_model','vhisSaso');
				$this->load->model('z_model','z');
				$range = $this->mssql_escape($this->input->post('frequency'));
				$dtFrom =  $this->mssql_escape($this->input->post('datefrom'));
				$dtTo =  $this->mssql_escape($this->input->post('dateto'));
				if(date('h:i:s A',strtotime($dtTo)) == "11:59:00 PM") {$dtTo = date('m/d/Y h:i:s A',strtotime('+59 second',strtotime($dtTo)));}
				$result = $this->vhisSaso->get_vhis32_data($dtFrom,$dtTo,$sid,$skd,$svd);
				$data['result'] = $result->result_object();
				$data['df'] = date('m/d/Y h:i:s A',strtotime($dtFrom));
				$data['dt'] = date('m/d/Y h:i:s A',strtotime($dtTo));
				$data['range'] = $range;$data['ctr']=$this;
				$this->load->view('john/Report_vhis28',$data);	
			break;		
			case "VHIS 29":
				$this->load->model('vhisSaso2_model','vhisSaso');
				$reportdata = $this->reportz->get_report_29x($df,$dt,$sid,$svd,$fq);
				$data = array('reportdata' => $reportdata,'df'=> $df,'dt'=> $dt,'svd'=>$svd,'frequency'=> $fq);$data['ctr']=$this;
				$this->load->view('template/RepTable 29_new',$data);
			break;		
			case "VHIS 30":
				$z = $this->s2->get_vhis32_data($df,$dt,$sid,$skd,$svd);
				if($fq=="D" || $fq=="W" || $fq=="PM"|| $fq=="M" || $fq=="Y"){	
					$dt = strtotime($dt);$dt = date('m/d/Y h:i:s A',$dt);		
				}
				$html = "";
				$data = array('z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt,'freq'=> $fq,'html'=>$html);
				$this->load->view('template/RepTable 30',$data);
			break;
			case "VHIS 31":
				$z = $this->s2->get_vhis32_data($df,$dt,$sid,$skd,$svd);
				if($fq=="D" || $fq=="W" || $fq=="PM"|| $fq=="M" || $fq=="Y"){		
					$dt = strtotime($dt);$dt = date('m/d/Y h:i:s A',$dt);
				}
				$html = "";
				$data = array('z' => $z,'df'=> $df,'svd'=> $svd,'dt'=> $dt,'freq'=> $fq,'html'=>$html);
				$this->load->view('template/RepTable 31',$data);
			break;
						
		}
	}
			
	public function exportTable(){
		header('Conten-Type:  application/vnd.ms-excel');
		header('Conten-Disposition: attachment; filename=test.xls');
		echo $_POST['table'];
	}
	
	public function getAllAgents(){
		$x = $this->z->getAgents();$html = "";
		foreach($x->result_object() as $row){$html .= "$row->User_Id,";}
		return $html;
	}
	
	public function getAllTrunk(){
		$x = $this->z->getTrunks();$html = "";
		foreach($x->result_object() as $row){$html .= "$row->TrunkId,";}
		return $html;
	}
	public function getAllSkills(){
		$x = $this->z->getSkillsets();$html = "";
		foreach($x->result_object() as $row){$html .= "$row->Skill_Id,";}
		return $html;
	}
	
	
	public function getAllServices(){
		$x = $this->z->getServices();$html = "";
		foreach($x->result_object() as $row){$html .= "$row->Service_Id,";}
		return $html;
	}
	
	public function getAllAttendants(){
		$x = $this->z->getAttendants();$html = "";
		foreach($x->result_object() as $row){$html .= "$row->Agent,";}
		return $html;
	}

	public function getAllTelex(){
		$x = $this->z->getTelex();$html = "";
		foreach($x->result_object() as $row){$html .= "$row->TelexServiceId,";}
		return $html;
	}
	
	public function getAllHotline(){
		$x = $this->z->getHotline();$html = "";
		foreach($x->result_object() as $row){$html .= "$row->Hotlines,";}
		return $html;
	}
	
	public function pdf(){
		ob_start();
		$this->load->library('Pdf');
		$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Report Generator');
		$pdf->SetTitle('Report Generator');
		$pdf->SetSubject('Report Generator');
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}
		$pdf->SetFont('', '', 10);
		$pdf->SetMargins(5, 5, 5);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->setPrintFooter(true);
		$pdf->AddPage();
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		$html = "";$html = $_POST['html'];
		$params = "";

		$preferences = array('HideToolbar' => true,'HideMenubar' => true,'HideWindowUI' => true,'FitWindow' => true,'CenterWindow' => true,
			'DisplayDocTitle' => true,'NonFullScreenPageMode' => 'UseNone','ViewArea' => 'CropBox','ViewClip' => 'CropBox','PrintArea' => 'CropBox',
			'PrintClip' => 'CropBox','PrintScaling' => 'AppDefault','Duplex' => 'DuplexFlipLongEdge', 'PickTrayByPDFSize' => true,'PrintPageRange' => array(1,1,2,3),'NumCopies' => 2
		);
		$html.= "<br/>".$pgNo;
		$pdf->setViewerPreferences($preferences);
		$pdf->writeHTML($html, true, 0, true, 0);
		$pdf->lastPage();
		ob_end_clean();
		$pdf->Output('Report'.date('YmdHis').'.pdf', 'I');
	}
	
	function excel(){
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename=Reports'.date('YmdHis').'.xls');
		header('Cache-Control: max-age=0');
		echo utf8_decode($_POST['xhtml']);
		exit;
	}
	
	function imagesave() {
		$rand = rand(1,10000);$path = "./uploads/";
		$url = "http://".$_SERVER['HTTP_HOST']."/reportsui/uploads/";
		$filename = "graph_".$rand.".png";
		$status = file_put_contents($path . $filename, file_get_contents($_POST['base64data']));
		if($status) { echo "<img src='".$url.$filename."'>"; }
	}

	public function generatePDF() {
		ini_set("pcre.backtrack_limit", "1000000");
		$this->load->library('pdfgenerator');
		$html = "<html><head>";
		$html .= "<title>Report</title>";
		if($this->input->post('thin') != "") {
			if ($this->input->post('thin') == "2"){
				$html .= "<style>
				@page { margin-right: 5px !important;margin-left: 15px !important;margin-bottom: 10px !important;font-family:Helvetica !important; font-size:7px !important;} 
				* { font-family:Helvetica !important; } table { table-layout:fixed; }</style>";
			}else{
				$html .= "<style>@page { margin: 0px !important;font-family:Helvetica !important;} 
				* { font-family:Helvetica !important; } </style>";
			} 
		}
		$html .= "</head>";$html .= "<body>".$this->input->post('html') . "</body></html>";
		if($this->input->post('orient') == "") {$orientation = "portrait";} else {$orientation = "landscape";}
		$this->pdfgenerator->generate($html,"Reports".date('YmdHis'),true,'Legal',$orientation);
	}
	
	public function generateXLS() {
		require_once APPPATH."/third_party/html2excel/HtmlExcel.php";
		$xls = new HtmlExcel();
		$xls->setCss(utf8_encode($this->input->post('css')));
		$xls->addSheet("Reports".date('YmdHis'), utf8_encode($this->input->post('html')));
		$xls->headers("Reports".date('YmdHis').".xls");
		echo $xls->buildFile();
	}
	
	public function generateDOC() {
		header("Content-Type: application/vnd.ms-word");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("content-disposition: attachment;filename=Reports".date('YmdHis').".doc");
		$html = "<style>  table { table-layout: fixed; }
				@page Section1 {size:1009pt 612pt; margin:1.0in 1.25in 1.0in 1.25in;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0; } div.Section1 {page:Section1;}
				@page Section2 {align: center; size:1009pt 612pt;mso-page-orientation:landscape;margin: 2cm 2cm 2cm 2cm;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0;} div.Section2 {page:Section2;}
				</style>";		
		if( utf8_encode($this->input->post('css'))== "qqq" ){ $html = "";
		
		$html .= "<style>@page Section1 {size:595.45pt 3890.7pt; margin:0.05in 0.05in 0.05in 0.05in;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0;} div.Section1 {page:Section1;}
					@page Section2 {size:3890.7pt 595.45pt;mso-page-orientation:landscape;margin:0.05in 0.05in 0.05in 0.05in;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0;} div.Section2 {page:Section2;}
				</style>";
		}elseif( utf8_encode($this->input->post('css'))== "qq" ){
			$html = "";
		
			$html = "<style>table { table-layout: fixed; } @page Section1 {size:1009pt 612pt; margin:1.0in 1.25in 1.0in 1.25in;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0; } div.Section1 {page:Section1;}
						@page Section2 {align: center; size:1009pt 612pt;mso-page-orientation:portrait;margin: 2cm 2cm 2cm 2cm;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0;} div.Section2 {page:Section2;}
					</style>";
		}elseif( utf8_encode($this->input->post('css'))== "gg" ){
			$html .= "<style> .imageexport { width:1300px;}</style>";
		}
		$html .= "<div class='Section2' style='font-size:8pt;font-family:arial'>";
		$html .= $this->input->post('html');$html .= "</div>";
		echo utf8_encode($html);
		exit();
	}
	public function generateHTML() {
		header("Content-Type: text/html");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("content-disposition: attachment;filename=Reports".date('YmdHis').".html");
		echo "<link href='http://10.80.25.34/reportsui/assets/css/bootstrap.min.css' rel='stylesheet'>";
		echo utf8_encode($this->input->post('css'));
		echo "\r\n";
		echo utf8_encode($this->input->post('html'));
		exit();	}
	public function generateDOCServer() {
		ob_start();$filename = "Reports".date('YmdHis').".doc";
		$html = "<style>table { table-layout: fixed; }@page Section1 {size:1009pt 612pt; margin:1.0in 1.25in 1.0in 1.25in;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0; } div.Section1 {page:Section1;}
						@page Section2 {align: center; size:1009pt 612pt;mso-page-orientation:landscape;margin: 2cm 2cm 2cm 2cm;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0;} div.Section2 {page:Section2;}
				</style>";
		if( utf8_encode($this->input->post('css'))== "qqq" ){
			$html = "";
			$html .= "<style>@page Section1 {size:595.45pt 3890.7pt; margin:0.05in 0.05in 0.05in 0.05in;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0;}div.Section1 {page:Section1;}@page Section2 {size:3890.7pt 595.45pt;mso-page-orientation:landscape;margin:0.05in 0.05in 0.05in 0.05in;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0;} div.Section2 {page:Section2;}</style>";
		}elseif( utf8_encode($this->input->post('css'))== "qq" ){
			$html = "";
			$html = "<style> table { table-layout: fixed; } @page Section1 {size:1009pt 612pt; margin:1.0in 1.25in 1.0in 1.25in;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0; } div.Section1 {page:Section1;}
						@page Section2 {align: center; size:1009pt 612pt;mso-page-orientation:portrait;margin: 2cm 2cm 2cm 2cm;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0;} div.Section2 {page:Section2;}
					</style>";
		}elseif( utf8_encode($this->input->post('css'))== "gg" ){
			$html .= "<style>.imageexport { width:1300px;} </style>";
		}
		$html .= "<div class='Section2' style='font-size:8pt;font-family:arial'>";
		$html .= $this->input->post('html');
		$html .= "</div>";
		echo utf8_encode($html);
		sleep(2);
		file_put_contents("./uploads/GeneratedReports/".$filename, ob_get_contents());
		sleep(2);
		ob_end_clean();
		echo $filename;
		}
	public function generateHTMLServer() {
		ob_start();
		$filename = "Reports".date('YmdHis').".html";
		echo "<link href='http://10.80.25.34/reportsui/assets/css/bootstrap.min.css' rel='stylesheet'>";
		echo utf8_encode($this->input->post('css'));
		echo "\r\n";
		echo utf8_encode($this->input->post('html'));
		sleep(2);
		file_put_contents("./uploads/GeneratedReports/".$filename, ob_get_contents());
		sleep(2);
		ob_end_clean();
		echo $filename;
		}
	public function generatePDFServer() {
		ini_set("pcre.backtrack_limit", "1000000");
		$this->load->library('pdfgenerator');
		$html = "<html><head>";
		$html .= "<title>Report</title>";
		if($this->input->post('thin') != "") {
			$html .= "<style>@page { margin: 0px !important;font-family:Helvetica !important; } * {font-family:Helvetica !important; } 
					body { margin: 0px !important;font-family:Helvetica !important; } table, table tr th, table tr td { font-family:Helvetica !important; }</style>";
		} else {
			$html .= "<style> * {font-family:Helvetica !important; } </style>";
		}
		$html .= "</head>";
		$html .= "<body>".$this->input->post('html') . "</body></html>";
		$orientation = "landscape";
		$currdate = date('YmdHis');
		$output = $this->pdfgenerator->generate($html,"Reports".$currdate,false,'Legal',$orientation);
		file_put_contents("./uploads/GeneratedReports/Reports".$currdate.".pdf",$output);
		echo "Reports".$currdate.".pdf";
		}
	public function generatePDFServerCustom() {
		ini_set("pcre.backtrack_limit", "1000000");
		$this->load->library('pdfgenerator');
		$html = "<html><head>";
		$html .= "<title>Report</title>";
		if($this->input->post('thin') != "") {
			$html .= "<style>@page { margin: 0px !important;font-family:Helvetica !important; } * {font-family:Helvetica !important; } 
					body { margin: 0px !important;font-family:Helvetica !important; } table, table tr th, table tr td { font-family:Helvetica !important; }</style>";
		} else {
			$html .= "<style> * {font-family:Helvetica !important; } </style>";
		}
		$html .= "</head>";
		$html .= "<body>".$this->input->post('html') . "</body></html>";
		$orientation = "landscape";
		$currdate = date('YmdHis');
		$output = $this->pdfgenerator->generate($html,"Reports".$currdate,false,'Legal',$orientation);
		file_put_contents("./uploads/pdf/Reports".$currdate.".pdf",$output);
		echo "Reports".$currdate.".pdf";
		}
	public function generateXLSServer() {
		ob_start();
		require_once APPPATH."/third_party/html2excel/HtmlExcel.php";
		$xls = new HtmlExcel();
		$xls->setCss(utf8_encode($this->input->post('css')));
		$xls->addSheet("", utf8_encode($this->input->post('html')));
		$currdate = date('YmdHis');$filename = "Reports".$currdate.".xls";$filename2 = "Reports".$currdate.".xlsx";
		echo $xls->buildFile();
		sleep(2);
		file_put_contents("./uploads/GeneratedReports/".$filename, ob_get_contents());
		sleep(2);
		ob_end_clean();
		echo $filename;
		}
	public function generateCSVServer() {
		ob_start();
		$currdate = date('YmdHis');
		$filename = "Reports".$currdate.".csv";
		echo $this->input->post('html');
		sleep(2);
		file_put_contents("./uploads/GeneratedReports/".$filename, ob_get_contents());
		sleep(2);
		ob_end_clean();
		echo $filename;
		}
	public function SaveAsReport() {
		$this->load->model('uidb_model','uidb');
		$data = file_get_contents('php://input');
		$data = json_decode($data, true);
		$filter = $this->getReportFilter($data['ReportCode']);
		$sid=""; $hid=""; $tlx=""; $tid=""; $atd=""; $svd=""; $skd=""; $aid="";
		if(in_array("SiteFilters",$filter,true)) { $sid = rtrim($data['SiteFilters'],','); }
		if(in_array("HotlineFilters",$filter,true)) {$hid = rtrim($data['HotlineFilters'],',');}
		if(in_array("TelexFilters",$filter,true)) {$tlx = rtrim($data['TelexFilters'],',');}
		if(in_array("TrunkFilters",$filter,true)) {	$tid = rtrim($data['TrunkFilters'],',');}
		if(in_array("AttendantFilters",$filter,true)) {$atd = rtrim($data['AttendantFilters'],',');}
		if(in_array("ServiceFilters",$filter,true)) {$svd = rtrim($data['ServiceFilters'],',');}
		if(in_array("CampaignFilters",$filter,true)) { }
		if(in_array("SkillsetFilters",$filter,true)) {$skd = rtrim($data['SkillsetFilters'],',');}
		if(in_array("AgentFilters",$filter,true)) {	$aid = rtrim($data['AgentFilters'],',');}
		$finaldata = array("FolderGuid"=> $data['FolderGuid'],	"ReportGuid"=> $data['ReportGuid'],"CreatedBy"=> $this->session->userdata('UserGuid'),
			"MyReportName"=> $data['MyReportName'],"IsRoot" => $data['IsRoot'],"SiteFilters" => $sid,"TrunkFilters" => $tid,"AttendantFilters"	=> $atd,
			"ServiceFilters" => $svd,"SkillsetFilters" => $skd,"AgentFilters" => $aid, "HotlineFilters" => $hid,"CampaignFilters" 	=> $data['CampaignFilters'],
			"TelexFilters" => $tlx,"IsScheduled" => $data['IsScheduled'],"LastGeneration" 	=> $data['LastGeneration'],"NextGeneration" => $data['NextGeneration'],
			"GenerationOption" 	=> $data['GenerationOption'],"TodayDate" => $data['TodayDate'], "DaysOfWeek" => $data['DaysOfWeek'],
			"SelectDatesDays" 	=> $data['SelectDatesDays'],"SelectDatesMonths" => $data['SelectDatesMonths'],"GenerationTime"	=> $data['GenerationTime'],
			"FileFormat" => $data['FileFormat'],"QuickRange" => $data['QuickRange'],"Frequency" => $data['Frequency'],"FromDateTime" => $data['FromDateTime'],
			"ToDateTime" => $data['ToDateTime'],"Status" => "PENDING");
		$url = explode(" ",$data['ReportCode']);
		$url = $url[1];
		$isInsert = $this->uidb->SaveAsReport($finaldata);
		if(!$isInsert){$return = array("result"	=> "Failed to Saved Transaction!","url"=> $data['ReportGuid']);}
		else{$return = array("result" => "Report Successfully Saved!","url" => $data['ReportGuid']);}
		echo json_encode($return);
		}
	function getReportFilter($reportname){
		$filter = [];switch ($reportname){
			case "VHIS 1A":$filter = array('SiteFilters','AgentFilters',);break;
			case "VHIS 1B":$filter = array('SiteFilters','AgentFilters',);break;
			case "VHIS 02A":$filter = array('SiteFilters','AgentFilters','ServiceFilters',);break;
			case "VHIS 02B":$filter = array('SiteFilters','AgentFilters','ServiceFilters',);break;
			case "VHIS 03":$filter = array('SiteFilters','AgentFilters','SkillsetFilters','ServiceFilters',);break;
			case "VHIS 04":$filter = array();break;
			case "VHIS 05A":$filter = array('SiteFilters','ServiceFilters',);break;
			case "VHIS 05B":$filter = array('SiteFilters','ServiceFilters',);break;
			case "VHIS 06A":$filter = array('TrunkFilters',);break;
			case "VHIS 06B":$filter = array('TrunkFilters',);break;
			case "VHIS 07":$filter = array('SiteFilters',);break;
			case "VHIS 08":$filter = array('SiteFilters',);break;
			case "VHIS 09":$filter = array('SiteFilters',);break;
			case "VHIS 10":$filter = array('SiteFilters',);break;
			case "VHIS 11":$filter = array('SiteFilters',);break;
			case "VHIS 12":$filter = array('SiteFilters','ServiceFilters',);break;
			case "VHIS 13":$filter = array('SiteFilters','SkillsetFilters','ServiceFilters',);break;
			case "VHIS 14":$filter = array();break;
			case "VHIS 15":$filter = array();break;
			case "VHIS 16":$filter = array('HotlineFilters',);break;
			case "VHIS 17":$filter = array();break;
			case "VHIS 18":$filter = array('SiteFilters','SkillsetFilters','ServiceFilters',);break;
			case "VHIS 19":$filter = array('SiteFilters',);break;
			case "VHIS 20":$filter = array('');break;
			case "VHIS 21":$filter = array('TelexFilters');break;
			case "VHIS 22":$filter = array('ServiceFilters','AttendantFilters',);break;
			case "VHIS 23":$filter = array('SiteFilters','SkillsetFilters','ServiceFilters',);break;
			case "VHIS 24":$filter = array('SiteFilters',);break;
			case "VHIS 25":$filter = array('SiteFilters',);break;
			case "VHIS 26":$filter = array('SiteFilters',);break;
			case "VHIS 27A":$filter = array('SiteFilters','ServiceFilters',);break;
			case "VHIS 27B":$filter = array('SiteFilters','ServiceFilters',);break;
			case "VHIS 28":$filter = array('SiteFilters','SkillsetFilters','ServiceFilters',);break;
			case "VHIS 29":$filter = array('SiteFilters','ServiceFilters',);break;
			case "VHIS 30":$filter = array('SiteFilters','SkillsetFilters','ServiceFilters',);break;
			case "VHIS 31":$filter = array('SiteFilters','SkillsetFilters','ServiceFilters',);break;
			case "VHIS 32":$filter = array('SiteFilters','ServiceFilters',);break;
			case "VHIS 33":$filter = array('SiteFilters','SkillsetFilters','ServiceFilters',);break;
			case "VHIS 34":$filter = array('SiteFilters','SkillsetFilters','ServiceFilters',);break;
		}return $filter;
		}
	function autofiledelete(){
		$files = scandir(FCPATH."uploads");
		foreach($files as $file) {if(!is_dir($file)){echo $file ."not directory" . filectime($file). "<br>";}}	
	}
	public function repcustom(){echo $this->input->get('RepID');}
	public function schedule(){
		$this->load->view('header');
		$df = date("m/d/Y H:i:s ", strtotime($this->mssql_escape($this->input->post("datefrom"))) );
		$dt = date("m/d/Y H:i:s", strtotime($this->mssql_escape($this->input->post("dateto"))) );
		$df2 = date("m/d/Y h:i:s a", strtotime($this->mssql_escape($this->input->post("datefrom"))) );
		$dt2 = date("m/d/Y h:i:s a", strtotime($this->mssql_escape($this->input->post("dateto"))) );
		$fq = $this->mssql_escape($this->input->post("frequency"));
		$aid = rtrim($this->mssql_escape($this->input->post("agentids")),',');
		$aid = $this->mssql_escape($this->input->post("agentids"));
		$srv = rtrim($this->mssql_escape($this->input->post("serviceids")),",");
		$htl = rtrim($this->mssql_escape($this->input->post("myhotlines")),",");		
		$tid= $this->mssql_escape($this->input->post("trunkids"));
		$atd= $this->mssql_escape($this->input->post("attendants"));
		$tlx= $this->mssql_escape($this->input->post("telex"));		
		if($aid==''){$aid=$this->getAllAgents();}
		if($tid==''){$tid=$this->getAllTrunk();}
		if($atd==''){$atd=$this->getAllAttendants();}
		if($tlx==''){$tlx=$this->getAllTelex();}
		if($htl==''){$htl='16211';}
		$sid = rtrim($this->mssql_escape($this->input->post("siteid")),',');
		if($sid==''){$sid='MOC,BALINTAWAK,STA ROSA';}
		$svd = $this->mssql_escape($this->input->post("serviceids"));
		if($svd==''){$svd=$this->getAllServices();}
		$skd = str_replace('undefined,', '',$this->mssql_escape($this->input->post('skillids')));
		if($skd==''){$skd=$this->getAllSkills();}
	}	
}
