<?php
include('xmlseclibs.php');//Get this file from http://code.google.com/p/xmlseclibs
class xauth_googleapps {
	
	public $SAML_response;
	public $acs_url;
	
	/**
	* Generate Response for the received Request and user
	* @param string SAMLRequest
	* @param string Gapps User (email)
	* @return void
	*/
	public function login($SAMLRequest, $loginEmail){
		$incoming = base64_decode($SAMLRequest);
		if(!$xml_string = gzinflate($incoming)){
			$xml_string = $incoming;
		}
		$xml = new DOMDocument();
		$xml->loadXML($xml_string);
		if($xml->hasChildNodes() && ($node = $xml->childNodes->item(0))){
			$authnrequest = array();
			foreach($node->attributes as $attr){
				$authnrequest[$attr->name] = $attr->value;
			}
			if($node->hasChildNodes()){
				foreach($node->childNodes as $childnode){
					if($childnode->hasAttributes()){
						$authnrequest[$childnode->nodeName]=array();
						foreach($childnode->attributes as $attr){
							$authnrequest[$childnode->nodeName][$attr->name] = $attr->value;
						}
					}else{
						$authnrequest[$childnode->nodeName]=$childnode->nodeValue;
					}
				}
			}
		}
		
		$this->acs_url = $authnrequest['AssertionConsumerServiceURL'];

		$response_params = array();
		$time = time();
		$response_params['IssueInstant'] = str_replace('+00:00','Z',gmdate("c",$time));
		$response_params['NotOnOrAfter'] = str_replace('+00:00','Z',gmdate("c",$time+300));
		$response_params['NotBefore'] = str_replace('+00:00','Z',gmdate("c",$time-30));
		$response_params['AuthnInstant'] = str_replace('+00:00','Z',gmdate("c",$time-120));
		$response_params['SessionNotOnOrAfter'] = str_replace('+00:00','Z',gmdate("c",$time+3600*8));

		$response_params['ID'] = $this->generateUniqueID(40);
		$response_params['assertID'] = $this->generateUniqueID(40);

		$response_params['issuer'] = $_SERVER['HTTP_HOST'];//Does not really matter as far as i could tell
		$response_params['email'] = $loginEmail;
		$response_params['x509'] = <<<X509
-----BEGIN CERTIFICATE-----

-----END CERTIFICATE-----
X509;


		$private_key = <<<RSA_PRIVATE_KEY
-----BEGIN RSA PRIVATE KEY-----

-----END RSA PRIVATE KEY-----
RSA_PRIVATE_KEY;
		
		$xml = new DOMDocument('1.0','utf-8');
		$resp = $xml->createElementNS('urn:oasis:names:tc:SAML:2.0:protocol','samlp:Response');

		$resp->setAttribute('ID',$response_params['ID']);
		$resp->setAttribute('InResponseTo',$authnrequest['ID']);
		$resp->setAttribute('Version','2.0');
		$resp->setAttribute('IssueInstant',$response_params['IssueInstant']);
		$resp->setAttribute('Destination',$authnrequest['AssertionConsumerServiceURL']);
		$xml->appendChild($resp);
		
		$issuer = $xml->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion','samlp:Issuer',$response_params['issuer']);
		$resp->appendChild($issuer);
		
		$status = $xml->createElementNS('urn:oasis:names:tc:SAML:2.0:protocol','samlp:Status');
		$resp->appendChild($status);

		$statusCode = $xml->createElementNS('urn:oasis:names:tc:SAML:2.0:protocol','samlp:StatusCode');
		$statusCode->setAttribute('Value', 'urn:oasis:names:tc:SAML:2.0:status:Success');
		$status->appendChild($statusCode);

		$assertion = $xml->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion','saml:Assertion');
		$assertion->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
		$assertion->setAttribute('ID',$response_params['assertID']);
		$assertion->setAttribute('IssueInstant',$response_params['IssueInstant']);
		$assertion->setAttribute('Version','2.0');
		$resp->appendChild($assertion);
		
		$assertion->appendChild($xml->createElement('saml:Issuer',$response_params['issuer']));

		$subject = $xml->createElement('saml:Subject');
		$assertion->appendChild($subject);
		
		$nameid = $xml->createElement('saml:NameID',$response_params['email']);
		
		$nameid->setAttribute('Format','urn:oasis:names:tc:SAML:2.0:nameid-format:email');
		$nameid->setAttribute('SPNameQualifier','google.com');
		$subject->appendChild($nameid);
		
		$confirmation = $xml->createElement('saml:SubjectConfirmation');
		$confirmation->setAttribute('Method','urn:oasis:names:tc:SAML:2.0:cm:bearer');
		$subject->appendChild($confirmation);
		
		$confirmationdata = $xml->createElement('saml:SubjectConfirmationData');
		$confirmationdata->setAttribute('InResponseTo',$authnrequest['ID']);
		$confirmationdata->setAttribute('NotOnOrAfter',$response_params['NotOnOrAfter']);
		$confirmationdata->setAttribute('Recipient',$authnrequest['AssertionConsumerServiceURL']);
		$confirmation->appendChild($confirmationdata);
		
		$condition = $xml->createElement('saml:Conditions');
		$condition->setAttribute('NotBefore',$response_params['NotBefore']);
		$condition->setAttribute('NotOnOrAfter',$response_params['NotOnOrAfter']);
		$assertion->appendChild($condition);
		
		$audiencer = $xml->createElement('saml:AudienceRestriction');
		$condition->appendChild($audiencer);
		
		$audience = $xml->createElement('saml:Audience','google.com');
		$audiencer->appendChild($audience);
		
		$authnstat = $xml->createElement('saml:AuthnStatement');
		$authnstat->setAttribute('AuthnInstant',$response_params['AuthnInstant']);
		$authnstat->setAttribute('SessionIndex','_'.$this->generateUniqueID(30));//$response_params['assertID']
		$authnstat->setAttribute('SessionNotOnOrAfter',$response_params['SessionNotOnOrAfter']);
		$assertion->appendChild($authnstat);
		
		$authncontext = $xml->createElement('saml:AuthnContext');
		$authnstat->appendChild($authncontext);
		
		$authncontext_ref = $xml->createElement('saml:AuthnContextClassRef','urn:oasis:names:tc:SAML:2.0:ac:classes:Password');
		$authncontext->appendChild($authncontext_ref);
		
		
		//Private KEY	
		$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
		$objKey->loadKey($private_key);
				
		//Sign the Assertion
		$objXMLSecDSig = new XMLSecurityDSig();
		$objXMLSecDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
		$objXMLSecDSig->addReferenceList(array($assertion), XMLSecurityDSig::SHA1,
			array('http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N),array('id_name'=>'ID','overwrite'=>false));
		$objXMLSecDSig->sign($objKey);
		$objXMLSecDSig->add509Cert($response_params['x509']);
		$objXMLSecDSig->insertSignature($assertion,$subject);
			

		$r = $xml->saveXML();
		$r = str_replace('<?xml version="1.0"?>','',$r);//Don't need that
		$r = base64_encode(stripslashes($r));//We assume post binding, the response is not deflated
		$this->SAML_response = $r;
	}

	private function generateUniqueID($length) {
    $chars = "abcdef0123456789";
    $chars_len = strlen($chars);
    $uniqueID = "";
    for ($i = 0; $i < $length; $i++)
      $uniqueID .= substr($chars,rand(0,15),1);
    return 'a'.$uniqueID;
  }
}


// =============== Example ====================

//Determine which user wants to login 
$loginUser = 'admin@404file.com';
$auth = new xauth_googleapps();
$auth->login($_GET['SAMLRequest'],$loginUser);



$form = <<<FORM
	<h3>Redirecting you to Google</h3>
 <form method="post" action="{$auth->acs_url}">
   <input type="hidden" name="RelayState" value="{$_GET['RelayState']}" />
   <input type="hidden" name="SAMLResponse" value="{$auth->SAML_response}" />
   <input type="submit" value="Submit" />
 </form>
 <script>//document.forms[0].submit();</script>
FORM;
print $form;
?>