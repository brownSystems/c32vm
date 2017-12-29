<?php 
/**
 * @version		1.3
 * @author		Beau Brown
 *
 * Setup Instructions:
 *  1. Place this file in the Cart32 template directory
 *  2. Include this file in any template file (Example: ItemList.html) using path: 'C:\ROOT_OF_WEBSITE\cgi-bin\cart32\CLIENTCODE\c32VM.class.php'
 *      -Example: include ('C:\ROOT_OF_WEBSITE\cgi-bin\cart32\CLIENTCODE\c32VM.class.php');   
 * 
 * Usage:
 *  
 *  $c32Vm = new c32Vm();
 *  
 *  $c32Vm->setDebug(TRUE); // Enables debuging output
 *  $c32Vm->setPrefix('YOUR_DB_TABLE_PREFIX'); 
 *  
 *  $cartID = $c32Vm->getCartId();
 *  $userID = $c32Vm->getVMUserID();
 *  $userInfo = $c32Vm->getUserInfos(); 
 *  $isSuperUser = $c32Vm->getIsSuperUser();  
 *  $total = $c32Vm->getTotal();            
 *  
 **/
 
//Autoload other classes used like Db
spl_autoload_register(function ($class_name) {
    include $class_name . '.class.php';
});

class c32Vm
{    
    //Config
    public $clientCode = '';    
    public $xmlURL = '';
    public $prefix = 'y9cxo_';
    public $superUserGroupID=8;
    
    //Deliverables
    public $cartID = '';
    public $vmUserID = 0;
    public $isActiveJoomlaSession = FALSE; 
    public $billingInfo = array();
    public $shippingInfo = array();
    public $userInfos = array();
    public $isSuperUser=false;
    
    //XML vars
    public $xmlData = '';
    public $xmlCartID = '';
    public $xmlProds = '';
    public $xmlVmUserID = 0;
    public $xmlPrefix = '';
    
    public $subTotal = 0;
    public $numItems = 0;
    public $prodCodeOnlyArr = array();
    public $prodIdOnlyArr = array();
    
    //Arrays with cart Item code as KEY
    public $vmProdIDArr = array();
    public $priceArr = array();
    public $qtyArr = array();
     
    //Vars From Request
    public $requestUserID = 0;
    public $requestCartID = '';
        
    //Debug vars
    public $debug = True;
    public $debugStr = '';
    public $debugLog = true;
    
    //Constructor
    function __construct() {
		    
       $this->setClientCode();
       
       //Loop over all request variables set data where possible
       $this->doRequestLoop();    
    }
   
  /**
	 * Returns the clientCode
	 *
	 * @author Beau B   
	 */
    public function getClientCode() {
      
      if(empty($this->clientCode)){
        $this->setClientCode();
      }
      
      return $this->clientCode;
    } 
    
  /**
	 * Sets the client code 
	 *
	 * @author Beau B
	 * @param string $clientCode   
	 */  
    public function setClientCode() {
        
      $arrForClientCode = array();
      $arrForClientCode = explode("_", $_SERVER['REQUEST_URI']);
	   
	    if(!empty($arrForClientCode[1])){
			   $this->clientCode = $arrForClientCode[1];
	    }		
	  } 
    
  /**
	 * Sets the Joomla table prefix
	 * 
	 * @author Beau B
	 * @param string $prefix   
	 */         
    public function setPrefix($prefix) {
       $this->prefix = $prefix;
    }   
    
  /**
	 * Gets the Joomla table prefix
	 * 
	 * @author Beau B
	 * @param string $prefix   
	 */         
    public function getPrefix() {
       return $this->prefix;
    }         
   
  /**
	 * Returns the CartID
	 *
	 * @author Beau B
	 */ 
    public function getCartId() {
     
     $clientCode = $this->getClientCode();
     
     if(!empty($clientCode)){ 
         
         if(empty($this->cartID)){
          $this->setCartID();
         }
         
        $this->debug("getCartId: CartID = ".$this->cartID);
        
        return $this->cartID;
     }else{
        $this->debug("getCartId: ERROR - No Client Code is Set");   
     }  
    }
  
  /**
	 * Sets the CartID
	 *
	 * @author Beau B
	 */     
    public function setCartID() { 
      $this->cartID =  $this->getRequestCartID();
      $this->debug("setCartID: CartID = ".$this->cartID);
    }  
   
  /**
	 * Returns the CartID found in the request variables
	 *
	 * @author Beau B
	 */
    public function getRequestCartID() {
      if(empty($this->requestCartID)){
         $this->debug("getRequestCartID: No CartID present");
        return false;
      }else{
        return $this->requestCartID;
      }
    } 
  
  /**
	 * Sets the CartID found in the request variables
	 *
	 * @author Beau B
	 */
    public function setRequestCartID($cartID) {
        $this->requestCartID = $cartID;
        $this->debug("setRequestCartID: CartID = ".$cartID);
    } 
   
  /**
	 * Returns  xmlCartID
	 *
	 * @author Beau B
	 */  
    public function getXMLCartID(){
      
      if(empty($this->xmlCartID)){ 
         $this->xmlCartID = $this->setXMLCartID();
      }
      
      return $this->xmlCartID;
    }
   
  /**
	 * Sets the xmlCartID from data in the cart summary XML
	 *
	 * @author Beau B
	 */  
    public function setXMLCartID(){
      
      $xmlData = $this->getCartSummaryData();
      
      if(!empty($xmlData)){
         $this->xmlCartID = $xmlData->CartID;
      }
    }  
    
  /**
	 * Returns Cart Summary XML data
	 *
	 * @author Beau B
	 */  
    public function getCartSummaryData(){
      
	  if(empty($this->xmlData)){
        $this->setCartSummary();
      } 
      return $this->xmlData;      
    } 
   
   /**
	 * Makes a request to cart summary XML file and sets xmlData with the resulting XML
	 *
	 * @author Beau B
	 */ 
    public function setCartSummary(){
      
      $clientCode =  $this->getClientCode();
      $cartID = $this->getCartId();
      
      //TODO: Add gets and sets or set this to local. 
      $this->xmlURL = "https://".$_SERVER['SERVER_NAME']."/cgi-bin/cart32.exe/".$clientCode."-cartsummaryxml?output=xml&skipshipping=y&passedadditemip=y&ip=".$cartID."&rndm=".rand();
     
      $this->debug("Cart Summary URL: ".$this->xmlURL);
    
      $xml = file_get_contents($this->xmlURL);   
      $this->xmlData = new SimpleXMLElement($xml);
     
    } 
    
  /**
	 * Returns the number of items in cart
	 *
	 * @author Beau B
	 */         
    public function getNumItems(){
      
      if(empty($this->numItems)) {
        $this->setNumItems();
      }
      
      return $this->numItems;     
       
    } 
    
  /**
	 * Sets the number of items in the cart from the xml data
	 *
	 * @author Beau B
	 */         
    public function setNumItems() {

      $numItems = "";
      $xmlData = "";
      
      $xmlData = $this->getCartSummaryData(); 
      
      if(!empty($xmlData)){
        $numItems = $xmlData->NumberOfItems;
      } 
      
      $this->numItems = $numItems;
    } 
    
    
  /**
	 * Returns the shopping cart total (xml subtotal)
	 *
	 * @author Beau B
	 */  
    public function getTotal(){
      
      if(empty($this->subTotal)) {
        $this->setTotal();
      }
      
      return $this->subTotal;  
    } 
    
  /**
	 * Sets the total (subtotal) in the cart from the xml data
	 *
	 * @author Beau B
	 */ 
    public function setTotal($total) {
    
      $total = "";
      $xmlData = "";
      
      $xmlData = $this->getCartSummaryData(); 
      
      if(!empty($xmlData)){
        $total = $xmlData->SubTotal;
      } 
      
      $this->subTotal = $total;
    } 
    
  /**
	 * Returns the Virtuemart User ID of the current user
	 *
	 * @author Beau B
	 */  
    public function getVMUserID() { 
    
      if(empty($this->vmUserID)){
         $this->setVMUserID();
      }
      
      $this->debug("getVMUserID: Returning vmUserID = ".$this->vmUserID); 
      
      return $this->vmUserID;
    }   
    
  /**
	 * Sets the Virtuemart UserID. Attempts to get it from VM database and tries request vars then XML if unsuccessful.
	 *
	 * @author Beau B
	 */  
    public function setVMUserID() {
       
      $userID = FALSE;
      
      if(!empty($this->cartID)){ 
      
        //Set UserID from VM user DB 
        $userID = $this->getUserIdFromVMDB(); //Returns false if no user id
        $this->debug("setVMUserID: Set userID from VMDB userID = ".$userID);  

        //No UserID retrieved from VM DB try to get UserID from Request Varialbes
        if(!$userID){
           $userID = $this->getRequestUserID(); //Returns false if no user id
           $this->debug("setVMUserID: No userID from VMDB getting from requests userID = ".$userID);  
        }
        //No UserID from VM DB or Request vars try UserID from XML product options userID field
        if(!$userID){
          $userID = $this->getUserIdFromXML(); //Returns false if no user id
          $this->debug("setVMUserID: No userID from VMDB or requests get from XML = ".$userID); 
        }
        
        if(!$userID){
         $this->debug("setVMUserID: WARNING - No User ID found.");  
        }else{
         $this->vmUserID = $userID;
         $this->debug("setVMUserID: UserID set to - ".$userID); 
        } 
        
      }else{
          $this->debug("setVMUserID: WARNING - No Cart ID. Exiting.");  
      } 
    } 
  
 
  /**
	 * Query the userID in the Virtuemart Database based on the CartID
	 *
	 * @author Beau B
	 */
    private function getUserIdFromVMDB(){
      
      $cartID = $this->getCartId();
      $prefix = $this->getPrefix();
      
      if(!empty($cartID)){  
        
    		$conn = Db::connMySQL();
    		
    		$stmt = $conn->prepare("SELECT `virtuemart_user_id` FROM `".$this->prefix."virtuemart_userinfos` WHERE `cartSessionID` = ? AND `address_type` = 'BT'");
    		$stmt->bind_param("s", $cartID);
    		$stmt->execute();
    		$result = $stmt->get_result();
    		 
    		if($result->num_rows > 0) {
                $row = $result->fetch_assoc(); 
                $this->debug("getUserIdFromVMDB: Success - USERID: ".$row['virtuemart_user_id']);  
                return $row['virtuemart_user_id'];
        }else{
              $this->debug("getUserIdFromVMDB: WARNING: No user found in VM database with cartID =".$cartID); 
        } 
      }else{
        $this->debug("getUserIdFromVMDB: WARNING: No CartID to query userID with."); 
      }    
       
      return false; 
    }
  
  /**
	 * Gets the userID in product option from cartSummaryXML
	 *
	 * @author Beau B
	 */
    private function getUserIdFromXML(){ 
      
      if($this->xmlVmUserID == 0){ //XML user id is not set try setting it with XML data
          $this->doXMLProductLoop(); //Set XML variables from prod loop
      }
      if($this->xmlVmUserID != 0){ 
        return $this->xmlVmUserID; //UserID found return userID
      }
        
        return false; //Set XML data but userID is still zero. Return false (no userID here)
    }  
    
  
  /**
	 * Returns the userID found in the request variables
	 *
	 * @author Beau B
	*/
    public function getRequestUserID() {
      
	  if(empty($this->requestUserID)){
        return false;
      }else{
        return $this->requestUserID;
      }
    } 
  
  /**
	 * Sets the UserID found in the request variables
	 *
	 * @author Beau B
	*/
    public function setRequestUserID($userID) {
        $this->requestUserID = $userID;
    }  
 
  /**
	 * Loops through all request variables and sets values where possible. 
	 *
	 * @author Beau B
	 */
    private function doRequestLoop(){
      
      foreach($_REQUEST as $key=>$value) {  
        
        $this->debug("doRequestLoop(): Key - ".$key." VALUE - ". $value);
        
        //****************USERID*******************//
        //If the value of the input is (h-userID)
        if($value == "h-userID"){ //This is the VMuserID option   
         $userIDValueField = str_replace("t","p",$key); //Replace t with p to get the name of the input containing the userID                                                              
         if(!empty($userIDValueField)){                                                                                                                                                        
            if(isset($_REQUEST[$userIDValueField])){                                            
              $userID =$_REQUEST[$userIDValueField]; //SET THE USERID
              if(!empty($userID)){
                $this->debug("doRequestLoop: SUCCESS - UserID: ".$userID);
                $this->setRequestUserID($userID);
              }else{
                $this->debug("doRequestLoop: WARNING - userID is blank or zero USERID: ".$userID);
              }
            }else{
              $this->debug("doRequestLoop: WARNING - TValue(".$userIDValueField.") not set");
            }
          }else{
              $this->debug("doRequestLoop: WARNING - TValue field not found for userID pfield");
          } 
        }
        
        //****************CART ID*******************//
        if($key== "HTTP_COOKIE2"){  
          
          $this->debug("doRequestLoop: Found Cookie Key: ".$key);
          $cookieArr = explode(";", $value);  
          
          foreach($cookieArr as $cookie){ 
            
            $c = explode("=", $cookie); 
            $clientCode = $this->getClientCode();
            if(!empty($clientCode)){               
              if($c[0] == " Cart32-".$clientCode){  
                $this->debug("doRequestLoop: Found CartID In Cookie Value =". $c[0]);
                //The Cart ID
                $cartID = $c[1];  
                //First add to cart after logout was giving old users cartID. Check the submitted IP value and make sure this doesn't happen
                if(isset($_REQUEST['ip'])){
                  if(!empty($_REQUEST['ip'])){
                    if($_REQUEST['ip'] != $cartID){
                      $cartID = $_REQUEST['ip'];
                      $this->debug("doRequestLoop : REQUEST[ip] override CartID Value = ".$cartID);
                    }
                  }
                }               
                $this->setRequestCartID($cartID); 
                $this->debug("doRequestLoop: Set CartID from COOKIE: CARTID= ". $cartID);
              }
            }else{
                $this->debug("doRequestLoop: ERROR - NO CLIENT CODE SET ");
            } 
          }//End foreach cookie            
        }//End if ket HTTP_COOKIE2
        
         //****************LOOK FOR IP REQUEST VAR*******************//
        if(strtolower($key)== "ip"){  
          $this->debug("doRequestLoop: Found IP Key: ".$key);
          if($this->requestCartID == "" || $this->requestCartID == 0){
            $this->debug("doRequestLoop: Request CartID is zero or blank");
              if($value != "" && $value !=0 && $value !="0" && $value !="undefined"){
                 $this->setRequestCartID($value); 
                 $this->debug("doRequestLoop: Set CartID from IP. CARTID=".$value);
              }
          }           
        }//End IP request var
        
      }//End loop over request vars   
    }
   
  /**
	 * Calling this will query Joomla, set the variable, and return the updated value.
	 *
	 * @author Beau B
	 */   
    public function getIsActiveJoomlaSession(){  
      
       $this->setIsActiveJoomlaSession(); //reset this no matter what because the session may have ended
       
       return $this->isActiveJoomlaSession;
    }
    
  /**
	 * Set the isActiveJoomlaSession based on Virtuemart DB value
	 *
	 * @author Beau B
	 */  
   public function setIsActiveJoomlaSession(){
      
      $isActiveJoomlaSession = $this->checkIsActiveJoomlaSession(); 
      $this->isActiveJoomlaSession = $isActiveJoomlaSession;
   }
    
  /**
	 * Queries the Joomla sessions table with userID and returns true or false.
	 *
	 * @author Beau B
	 */  
    private function checkIsActiveJoomlaSession(){ 
      
      $vmUserID = $this->getVMUserID();
      $prefix = $this->getPrefix();
      
      if(!empty($vmUserID)){
       
    		$conn = Db::connMySQL();
    		 
    		$stmt = $conn->prepare("SELECT `session_id` FROM `".$prefix."session`  WHERE `userid` = ?");
    		$stmt->bind_param("i", $vmUserID);
    		$stmt->execute();
    		$result = $stmt->get_result();
    		 
    		if($result->num_rows > 0) {
          $this->debug("checkIsActiveJoomlaSession: Session is Active"); 
          return true;
        }else{
          $this->debug("checkIsActiveJoomlaSession: Session is NOT Active"); 
          return false;
        }
              
      }else{
        $this->debug("checkIsActiveJoomlaSession: WARNING - No UserID to check session with"); 
        return false;
      }
    }
    
  /**
	 * Gets Billing and Shipping Information 
	 *
	 * @author Beau B
	 */ 
    public function getUserInfos(){
      
      $this->queryUserInfos();  
      
      $this->userInfos['bInfo']=$this->getBillingInfo();
      $this->userInfos['sInfo']=$this->getShippingInfo();
      
    
       return $this->userInfos;
    }
    
  /**
	 * Gets the Users Billing Information
	 *
	 * @author Beau B
	 */ 
    public function getBillingInfo(){
       return $this->billingInfo;
    }
    
  /**
	 * Sets the Users Billing Information
	 *
	 * @author Beau B
	 */ 
    public function setBillingInfo($arrBillInfo){
       $this->billingInfo = $arrBillInfo;
    }
    
  /**
	 * Gets the Users Shipping Information
	 *
	 * @author Beau B
	 */ 
    public function getShippingInfo(){
       return $this->shippingInfo;
    }
    
  /**
	 * Sets the Users Shipping Information
	 *
	 * @author Beau B
	 */ 
    public function setShippingInfo($arrShipInfo){
       $this->shippingInfo = $arrShipInfo;
    }
  
  /**
	 * Queries the Virtuemart database to get billing/shipping info for user. 
	 *
	 * @author Beau B
	 */     
    private function queryUserInfos(){
       
      $arrBilling = array();
      $arrShipping = array();  
      
      $prefix = $this->getPrefix();
      $cartID = $this->getCartId();
      $vmUserID = $this->getVMUserID();
	  
	    $conn = Db::connMySQL();	
	  
      $this->debug("queryUserInfos: Start"); 
		  
  	  $stmt = $conn->prepare("SELECT * FROM `".$prefix."virtuemart_userinfos` WHERE `cartSessionID` = ? AND `virtuemart_user_id` = ?  AND `address_type` = 'BT'");
  	  $stmt->bind_param("si", $cartID, $vmUserID);
  	  $stmt->execute();
  	  $result = $stmt->get_result();		
  	   
       if($result->num_rows > 0) {
          
          $bInfoRow=$result->fetch_assoc(); 
          
          //User Infos doesn't have email so get it from the user table			
      		$stmt = $conn->prepare("SELECT email FROM `".$prefix."users` WHERE `id` = ?");
      		$stmt->bind_param("i", $bInfoRow['virtuemart_user_id']);
      		$stmt->execute();
      		$result = $stmt->get_result();	
          $jUserRow=$result->fetch_assoc();  
          
          $bInfoRow['email']=$jUserRow['email'];
          
          //UserInfo only contains countryID. Query the text for that ID
          $bInfoRow['countryName'] = $this->getCountry($bInfoRow['virtuemart_country_id']);
          
          //Query the billing state
  		    $bInfoRow['stateName']=$this->getState($bInfoRow['virtuemart_state_id']);
          
          $this->setBillingInfo($bInfoRow);
        }
       
        //Query the shipping info    
    	  $stmt = $conn->prepare("SELECT * FROM `".$prefix."virtuemart_userinfos` WHERE `virtuemart_user_id` = ?  AND `address_type` = 'ST'");
    	  $stmt->bind_param("i", $vmUserID);
    	  $stmt->execute();
    	  $result = $stmt->get_result();		
    	   
    	  if($result->num_rows > 0) { 
    	  
            $sInfoRow = $result->fetch_assoc();  
            
             //Query the shipping country
            $sInfoRow['countryName']=$this->getCountry($sInfoRow['virtuemart_country_id']);
              
            //Query the shipping state
            $sInfoRow['stateName']=$this->getState($sInfoRow['virtuemart_state_id']);
            
            $this->setShippingInfo($sInfoRow);
          }  
    }
	
  	/**
  	 * Returns the name of the state based on the stateID
  	 * 
  	 * @author Beau B
  	 * @param int $stateID
  	 */
  	private function getState($stateID){
  		
      $conn = Db::connMySQL();	
  		$stateName = "";
  		$prefix = $this->getPrefix();
  		$stmt = $conn->prepare("SELECT state_2_code FROM `".$prefix."virtuemart_states` WHERE `virtuemart_state_id` = ?");
  		$stmt->bind_param("i", $stateID);
  		$stmt->execute();
  		$result = $stmt->get_result();
  		
  		if($result->num_rows > 0) { 		
  			$row=$result->fetch_assoc();  
  			$stateName = $row['state_2_code'];
  		}
  		
          return $stateName;	
  	}
	
  	/**
  	 * Returns the name of the country based on the countryID
  	 * 
  	 * @author Beau B
  	 * @param int $countryID
  	 */
    private function getCountry($countryID){
  		
      $conn = Db::connMySQL();	
  		$countryName = "";          
  		$prefix = $this->getPrefix();
  		$stmt = $conn->prepare("SELECT country_name FROM `".$prefix."virtuemart_countries` WHERE `virtuemart_country_id` = ?");
  		$stmt->bind_param("i", $countryID);
  		$stmt->execute();
  		$result = $stmt->get_result();
  		
  		if($result->num_rows > 0) { 		
  			$row=$result->fetch_assoc();  
  			$countryName = $row['country_name'];
  		}
  		
          return $countryName;
  	}
   
   /**
	 * Returns the XML for products in the cart
	 *
	 * @author Beau B
	 */ 
    public function getXmlProds(){
        
      if(empty($this->xmlProds)){
          $this->setXmlProds();
      }    
     
     return $this->xmlProds; 
    }
     
  /**
	 * Sets the xmlProds with XML of products in the cart
	 *
	 * @author Beau B
	 */ 
    public function setXmlProds(){        
    
      $items = "";  
      $xmlData = $this->getCartSummaryData();
      
      $items=$xmlData->items[0];
      
      $this->xmlProds = $items;  
    }
    
  /**
	 * Loops over products in XML and sets variables
	 *
	 * @author Beau B
	 */
    private function doXMLProductLoop(){
      
      $this->debug("productLoopFromXML: START");      

      //Define vars
      $xmlProds = $this->getXmlProds();
      $xmlUserID = 0;
      $xmlPrefix = '';
      
      //Using these to check for error in cart software
      $errUserID = false;
      $errPrefix = false;
      $arrUserIDs = array();
      $arrPrefix = array();
      
     if(!empty($xmlProds)){ 
      foreach($xmlProds as $item){ //Loop over products in XML
        
        //Define Variables
        $productID = 0;
        $code = (string)$item->code[0];
        $price = (string)$item->originalprice[0];
        $qty = (int)$item->qty[0];
        
        $this->debug("productLoopFromXML: In Prod Loop - Product Name = ".$item->itemname.", Code = ".$code.", Price = ".$price.", Qty = ".$qty);
        
        //Options for this item                                                                                                
        $options = $item->cartoptions[0]; 
         
        if($options){ 
          foreach($options as $option){ //Loop over options for this product.
            
              $this->debug("productLoopFromXML: In Prod Options Loop - Product Name = ".$item->itemname.", Code =".$code);
              
              if(!empty($option->optionvalue)){ //make sure the option value isn't empty before we continue
                if($option->optionname == "userID"){
                      if($xmlUserID != $option->optionvalue){ //Only set this if the value has changed. 
                        $xmlUserID = $option->optionvalue;
                        $arrUserIDs[] = $xmlUserID;
                      }
                      $this->debug("productLoopFromXML: UserID Found in XML - UserID = ".$xmlUserID);
                }
              
                if($option->optionname == "prefix"){
                    if($xmlPrefix != $option->optionvalue){ //Only set this if the value has changed. 
                      $xmlPrefix  =$option->optionvalue;
                      $arrPrefix[] = $xmlPrefix;
                    }
                    $this->debug("productLoopFromXML: Prefix Found in XML - Prefix = ".$xmlPrefix);
                }
              
                if($option->optionname == "productID"){
                     $productID = intval($option->optionvalue);
                     $this->debug("productLoopFromXML: ProductID Found in XML - ProductId = ".$productID);
                }
          
                if($option->optionname == "categoryID"){ //Not implemented on all VM versions
                     $categoryID = intval($option->optionvalue);
                     $this->debug("productLoopFromXML: CategoryID Found in XML - CategoryId = ".$categoryID);
                }
              } 
           }//End loop over options 
         }else{
            $this->debug("productLoopFromXML: WARNING! - No Product Options found");
         }
         //Push to arrays
         array_push($this->prodCodeOnlyArr,$code);  //Array of just cart codes
         $this->prodIdOnlyArr[] = $productID;       //Array of just prodIDs
         $this->vmProdIDArr[$code] = $productID;    //Array of productIDs with cart code as key
         $this->priceArr[$code] = floatval($price); //Array of prices with cart code as key 
         $this->qtyArr[$code] = $qty;               //Array of QTYs with cart code as key
        
       }//end loop prods
       
       //Error Checking UserIDs
       if(count($arrUserIDs) > 1){
           $this->debug("productLoopFromXML: ERROR! - Multiple UserIDs in Cart");
           $errUserID = true;
           foreach($arrUserIDs as $uid){
                $this->debug("Error UserIds: UserID = ".$uid);
           }  
       }
       //Error Checking Prefix
       if(count($arrPrefix) > 1){
           $this->debug("productLoopFromXML: ERROR! - Multiple prefix's in Cart");
           $errPrefix = true;
           foreach($arrPrefix as $prefx){
                $this->debug("Error Prefix: Prefix = ".$prefx);
           }  
       }
       
      }else{
           $this->debug("productLoopFromXML: WARNING! - xmlProds is empty");
      }
        //Set UserID and Prefix
        if(!$errUserID){
          $this->xmlVmUserID = $xmlUserID;
        }
        if(!$errPrefix){
          $this->xmlPrefix = $xmlPrefix;
        }
    }
    
  /**
	 * Determines if user is a Super User in VM and sets isSuperUser accordingly
	 *
	 * @author Beau B
	 */ 
    public function setIsSuperUser(){
      
      $this->debug("setIsSuperUser() - START"); 
      
      $hasSuperUserGroup = false;  
      $arrUserGroups = $this->getUserGroupsVMDB();
      
      foreach($arrUserGroups as $groupID){
        if($groupID == $this->superUserGroupID){
          $hasSuperUserGroup = true; 
        } 
      }
      
      if($hasSuperUserGroup){
        $this->isSuperUser = true;
        $this->debug("setIsSuperUser() - isSuperUSer = true"); 
      }else{
        $this->isSuperUser = false;
        $this->debug("setIsSuperUser() - isSuperUSer = false"); 
      }
      
      $this->debug("setIsSuperUser() - END"); 
    }
    
   /**
	 * Returns isSuperUser
	 *
	 * @author Beau B
	 */
   public function getIsSuperUser(){
      
      $this->debug("getIsSuperUser - START "); 
      
      if(!$this->isSuperUser && !empty($this->vmUserID)){
        $this->setIsSuperUser();
      }else{
        $this->debug("getIsSuperUser: No UserID or Already Super User "); 
      }
      
      $this->debug("getIsSuperUser - END "); 
      
      return $this->isSuperUser;
   }
  
  /**
	 * Returns array of user group IDs assosciated with current user
	 *
	 * @author Beau B
	 */
  private function getUserGroupsVMDB(){
    
		if(!empty($this->vmUserID)){  
        
			  $conn = Db::connMySQL();	
    		$prefix = $this->getPrefix();
    		$stmt = $conn->prepare("SELECT `group_id` FROM `".$prefix."user_usergroup_map`  WHERE  `user_id` = ?");
    		$stmt->bind_param("i", $this->vmUserID);
    		$stmt->execute();
    		$result = $stmt->get_result();
    		
			if($result->num_rows > 0) {
				  
				$row = $result->fetch_assoc();
				  
				$this->debug("getUserGroupsVMDB: Success - Returning Array of User Groups");  
				   
				return $row;
			}else{
			  $this->debug("getUserGroupsVMDB: WARNING - No UserGroups found"); 
			}   
		}else{
			$this->debug("getUserGroupsVMDB: WARNING - No UserID"); 
		} 
            
      return false; 
  }    
 
  /**
	 * Sets debuging on(true) or off(false)
	 *
	 * @author Beau B
	 */  
    public function setDebug($debug, $logFile=false) {
       
       $this->debug = $debug; 
       if($logFile){
          $this->debugLog = TRUE;
       } 
    }
  
  /**
	 * Returns the debugging string
	 *
	 * @author Beau B
	 */
    public function getDebug() {
    
        return "<!----------DEBUG BEGIN: ".$this->debugStr."------------>";
    }
  
  /**
	 * Appends debug message to the debug string if debuging is enabled.
	 *
	 * @author Beau B
	 */         
    private function debug($string){
       
       if($this->debug){
          $stringForDebug = "\n".$string."\n";
          $this->debugStr.=$stringForDebug;
          
            
           if($this->debugLog){
            $logDate = date('Ymd');
            file_put_contents("log".$logDate.".txt", $stringForDebug, FILE_APPEND | LOCK_EX);
           }
       }
    }    
    
} //End Class
            
?>