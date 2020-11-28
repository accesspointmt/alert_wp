<?php
namespace APG;

class Method {
    const GET = "GET";
    const POST = "POST";
}

class Target {
    const Blank = "_blank";
    const Self = "_self";
    const Parent = "_parent";
    const Top = "_top";
}

class RemotePost {
    public $URL;
	public $Data = array();
	public $Method = "POST";
	public $Target = Target::Self;
	public $FormName = "formPost";

	function Post($endResponse = true) {
		$pageResponse = '<html><head>';
		
		if($this->Method == Method::GET){
			$pageResponse .= '<meta http-equiv="refresh" content="1; url='.$this->URL.'?';
			foreach ($this->Params as $index => $value) {
				$pageResponse .= $index.'='.$value.'&';
			}
			$pageResponse .= '" />';
		}

		$pageResponse .= '</head><body onload="document.'.$this->FormName.'.submit()">';
		
		if($this->Method == Method::POST){
			$pageResponse .= '<form name="'.$this->FormName.'" method="'.$this->Method.'" action="'.$this->URL.'" target="'.$this->Target.'">';
			foreach ($this->Params as $index => $value) {
				$pageResponse .= '<input name="'.$index.'" type="hidden" value="'.$value.'" />'; 
			}
			$pageResponse .= '</form></body></html>';
		}
		
		echo $pageResponse;
		if($endResponse) die();
	}

}



?>
