<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

define('API_URL','http://lib.staging.justbooksclc.com/');

require '../vendor/autoload.php';
require '../includes/functions.php';

session_start();
$app = init();


$authenticate = function ($request, $response, $next) {
    if (!isset($_SESSION['user'])) {
        return $response->withRedirect('/login/');
    } else {
        return $next($request, $response);
    }
};

function serverHeader(){
    $emailId = "test@strata.co.in";
    $strNum = time();
    $finalRes = $strNum;
    $shaStr = ($finalRes+42);
    $autNumber = sha1($shaStr);

    $httpHeader = array();
    $httpHeader[] = 'Content-Type: application/xml';
    $httpHeader[] = 'Connection: Keep-Alive';
    $httpHeader[] = 'X-STRATA-TIME:'.$finalRes;
    $httpHeader[] = 'X-STRATA-EMAIL:'.$emailId;
    $httpHeader[] = 'X-STRATA-AUTH:'.$autNumber;
    return $httpHeader;
}

function curlFunction($url){

    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_URL.$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    return $raw_data;
}

function curlFunctionXML($url,$xml){

    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_URL.$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    return $raw_data;
}

function readKioskid()
{
    $kioskid = '';
    $handle = @fopen("conf.txt", "r");
    if ($handle) {
        while (($buffer = fgets($handle, 4096)) !== false) {
            $kioskid = explode('=',$buffer);
        }
    }
    return $kioskid[1];
}
$_SESSION['kioskid'] = readKioskid();

$app->get('/test', function(Request $request, Response $response){
        $emailId = "test@strata.co.in";
        $strNum = time();
        $finalRes = $strNum;
//    1267170461
        $shaStr = ($finalRes+42);
        $autNumber = sha1($shaStr);
        $opID = $finalRes;

    $httpHeader = array();
    $httpHeader['X-STRATA-TIME'] = $finalRes;
    $httpHeader['X-STRATA-EMAIL'] = $emailId;
    $httpHeader['X-STRATA-AUTH'] = $autNumber;
    echo print_r($httpHeader);
});

$app->get('/user', function (Request $request, Response $response) {
    $response_data = $request->getQueryParams();
    $_SESSION['name'] = $response_data['name'];
    $response = $this->view->render($response, 'index.mustache', array('name'=>$_SESSION['name']));
    return $response;
});

$app->get('/branch', function (Request $request, Response $response) {
    $response_data = $request->getQueryParams();
    $_SESSION['branch_id'] = $response_data['branch_id'];
    $_SESSION['city_id'] = $response_data['city_id'];
    $response = $this->view->render($response, 'index_branch.mustache');
    return $response;
});

$app->get('/update_location', function (Request $request, Response $response) {
    $response = $this->view->render($response, 'update_location_branch.mustache');
    return $response;
});

$app->get('/search', function (Request $request, Response $response) {
    $response = $this->view->render($response, 'search_branch.mustache');
    return $response;
});

$app->get('/sort', function (Request $request, Response $response) {
    $response = $this->view->render($response, 'sort_branch.mustache');
    return $response;
});

$app->get('/closed_card', function (Request $request, Response $response) {
    $response = $this->view->render($response, 'closed_card_branch.mustache',array('branchid'=>$_SESSION['branch_id']));
    return $response;
});

$app->get('/audit', function (Request $request, Response $response) {
    $branchid = $_SESSION['branch_id'];
    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/branches/".$branchid.".xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    $branchname = $final_array['name'];
    $pending = 0;$audit_id=0;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/get_stock_audit_status/".$branchid.".xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    $audit_status = $final_array['status'];
    if($audit_status == 'Pending'){
        $pending = 1;
        $audit_id = $final_array['id'];
    }
    $response = $this->view->render($response, 'audit_branch.mustache',array('branchname'=>$branchname,'status'=>$pending,'audit_id'=>$audit_id));
    return $response;
});

$app->get('/stopAudit', function (Request $request, Response $response) {
    $branchid = $_SESSION['branch_id'];
    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/complete_stock_audit/".$branchid.".xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo 'success';
});

$app->get('/startAudit', function (Request $request, Response $response) {
    $branchid = $_SESSION['branch_id'];
    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/start_stock_audit/".$branchid.".xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo 'success';
});

$app->get('/getAuditCardDetails', function (Request $request, Response $response) {
    $branchid = $_SESSION['branch_id'];
    $card = $request->getQueryParams()['card'];

    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/branches/".$branchid.".xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo json_encode($final_array);
});

$app->get('/stock_audit', function(Request $request, Response $response){
    $branchid = $_SESSION['branch_id'];
    $booknumber = trim($request->getQueryParams()['book']);
    $shelf = trim($request->getQueryParams()['shelf']);
    $xml = "<stock_audit><kbranchid>$branchid</kbranchid><booknumber>$booknumber</booknumber><shelf_location>$shelf</shelf_location><title_id></title_id><user>admin</user></stock_audit>";
    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.justbooksclc.com/stock_audit_new.xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo json_encode($final_array['book']);
});

$app->get('/updateClosedCard', function(Request $request, Response $response){
    $request_data = $request->getQueryParams();
    $branch_id = $_SESSION['branch_id'];
    $member_card = $request_data['card'];
    $member_id = $request_data['member_id'];
    $closure_id = $request_data['closure_id'];
    $xml = '<closed_card><membership_no>'.$member_card.'</membership_no><member_id>'.$member_id.'</member_id><sendout_in></sendout_in><received_in>'.$branch_id.'</received_in><closure_id>'.$closure_id.'</closure_id></closed_card>';
    $result = curlFunctionXML("closed_cards.xml",$xml);
    $xml_response = simplexml_load_string($result);
    $json = json_encode($xml_response);
    echo json_encode(json_decode($json,TRUE));
});

$app->get('/recieve_book', function (Request $request, Response $response) {
    $response = $this->view->render($response, 'receive_book_branch.mustache',array('branchid'=>$_SESSION['branch_id']));
    return $response;
});

$app->get('/sending_out', function (Request $request, Response $response) {
    $response = $this->view->render($response, 'sending_out_branches.mustache',array('branchid'=>$_SESSION['branch_id']));
    return $response;
});

$app->get('/getSendoutOptions', function (Request $request, Response $response) {
    $branchid = $_SESSION['branch_id'];
    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/sending_out_reasons.xml?branch_id=$branchid");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo json_encode($final_array['sending-out-reason']);
});

$app->get('/getSendoutBranches', function (Request $request, Response $response) {
    $branchid = $_SESSION['branch_id'];
    $request_data = $request->getQueryParams();
    $option = $request_data['option'];
    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/branches.xml?kiosk_id=$branchid&transfer_type=$option");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo json_encode($final_array['branch']);
});

$app->post('/updateSendingBooks', function (Request $request, Response $response) {
    $request_data = $request->getParsedBody();
    $branchid = $_SESSION['branch_id'];
    $batch = $request_data['batch'];
    $reason = $request_data['reason'];
    $send_branch = $request_data['send_branch'];
    $books = rtrim($request_data['books'],',');
    $books_list = explode(',',$books);
    $books_xml = '';
    foreach($books_list as $book){
        $books_xml .= "<book-no>".$book."</book-no>";
    }
    $final_xml = "<send-out><branch-id>$send_branch</branch-id><txn-branch-id>2</txn-branch-id><reason>
                $reason</reason><book-nos type='array'>$books_xml</book-nos><batch-no>$batch</batch-no>
                <username>TJPNAGAR1</username></send-out>";
    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/send_out.xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $final_xml);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo json_encode($final_array);
});

$app->get('/fulfill_books', function (Request $request, Response $response) {
    $response = $this->view->render($response, 'fulfill_branches.mustache',array('branchid'=>$_SESSION['branch_id']));
    return $response;
});

$app->get('/getFulfillBranches', function (Request $request, Response $response) {
    $branchid = $_SESSION['branch_id'];
    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/branches/fullfillment_branches/".$branchid.".xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo json_encode($final_array['branch']);
});

$app->get('/getWareHouseCardDetails', function(Request $request, Response $response){
    $request_data = $request->getQueryParams();
    $branch_id = $_SESSION['branch_id'];
    $selected_branch = $request_data['sel_branch'];
    $final_array = [];
    $card = $request_data['card'];
    $result = curlFunction("branches.xml?card_id=".trim($card));
    $xml = simplexml_load_string($result);
    $json = json_encode($xml);
    $final_array[] = json_decode($json, TRUE);

    $result1 = curlFunction("member_plans/get_card_details".trim($card).".xml");
    $xml1 = simplexml_load_string($result1);
    $json1 = json_encode($xml1);
    $final_array[] = json_decode($json1, TRUE);

    $result2 = curlFunction("branches/".$selected_branch.".xml");
    $xml2 = simplexml_load_string($result2);
    $json2 = json_encode($xml2);
    $final_array[] = json_decode($json2, TRUE);

    echo json_encode($final_array);
});

$app->post('/getConsignmentNumber', function(Request $request, Response $response){
    $request_data = $request->getParsedBody();
    $consignor_id = $request_data['consignor_id'];
    $consignor_name = $request_data['consignor_name'];
    $consignee_id = $request_data['consignee_id'];
    $consignee_name = $request_data['consignee_name'];

    $final_xml = "<consignment><origin_id>$consignor_id</origin_id><destination_id>$consignee_id</destination_id><consignor>$consignor_name</consignor><consignee>$consignee_name</consignee></consignment>";
//    echo $final_xml;die;
    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/consignments.xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $final_xml);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo json_encode($final_array['id']);
});

$app->get('/processConsignment', function(Request $request, Response $response){
    $request_data = $request->getQueryParams();
    $branchid = $_SESSION['branch_id'];
    $book = $request_data['book'];
    $batch = $request_data['batch'];
//    echo $book."asas".trim($batch,'"');die;
    $final_xml = "<good><consignment_id>$batch</consignment_id><book-no>$book</book-no></good>";
    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/goods.xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $final_xml);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo json_encode($final_array);
});

$app->get('/deleteIBTR', function(Request $request, Response $response){
    $request_data = $request->getQueryParams();
    $branchid = $_SESSION['branch_id'];
    $ibtr = $request_data['ibtr'];
    $final_xml = "<_method>delete</_method>";
    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/goods/".$ibtr.".xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $final_xml);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo json_encode($final_array);
});

$app->get('/getConsignmentDetails', function(Request $request, Response $response){
    $request_data = $request->getQueryParams();
    $branchid = $_SESSION['branch_id'];
    $consignment = $request_data['consignment'];

    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/consignments/".$consignment.".xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    $raw_data = curl_exec($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json, TRUE);
    echo json_encode($final_array);
});

$app->get('/updateConsignment', function(Request $request, Response $response){
    $request_data = $request->getQueryParams();
    $branchid = $_SESSION['branch_id'];
    $consignment = $request_data['number'];

    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/consignments/".$consignment."/pickup.xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    $raw_data = curl_exec($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json, TRUE);
    echo json_encode($final_array);
});

$app->post('/updateRecieveBooks', function (Request $request, Response $response) {
    $request_data = $request->getParsedBody();
    $branchid = $_SESSION['branch_id'];
    $batch = $request_data['batch'];
    $books = rtrim($request_data['books'],',');
    $books_list = explode(',',$books);
    $books_xml = '';
    foreach($books_list as $book){
        $books_xml .= "<book-no>".$book."</book-no>";
    }
    $final_xml = "<receive><branch-id>$branchid</branch-id><book-nos type='array'>$books_xml</book-nos><batch-no>$batch</batch-no></receive>";
    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/receive_book.xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $final_xml);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo json_encode($final_array['jb-books']['jb-book']);
});

$app->get('/getSearchResults', function(Request $request, Response $response){
//    $request_data = $request->getQueryParams();
//    $branch_id = 2;
//    $text = $request_data['search_text'];
//    $result = curlFunction("api/search.xml?search_text=".$text."&branch_id=".$branch_id."&start=1&page_size=50");
//    $xml = simplexml_load_string($result);
//    $json = json_encode($xml);
//    $final_array = json_decode($json, TRUE);
//    echo json_encode($final_array['SearchDetails']['SearchDetail']);
    echo "[{\"titleBasic\":{\"title\":\"2 STATES THE STORY OF MY MARRIAGE [PB]\",\"titleId\":\"193823\",\"titleType\":\"B\",\"authorName\":\"BHAGAT, CHETAN\",\"totalCount\":\"108\",\"avlCount\":\"0\",\"totalBrhCount\":\"7\",\"language\":\"Kannada\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"THE SINS OF THE FATHER\",\"titleId\":\"289178\",\"titleType\":\"B\",\"authorName\":\"Jeffrey Archer\",\"totalCount\":\"90\",\"avlCount\":\"0\",\"totalBrhCount\":\"12\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"THE 3 MISTAKES OF MY LIFE [PB]\",\"titleId\":\"193826\",\"titleType\":\"B\",\"authorName\":\"CHETAN BHGAT\",\"totalCount\":\"79\",\"avlCount\":\"0\",\"totalBrhCount\":\"9\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"ONE NIGHT@ THE CALL CENTRE [PB]\",\"titleId\":\"193825\",\"titleType\":\"B\",\"authorName\":\"CHETAN BHGAT\",\"totalCount\":\"104\",\"avlCount\":\"0\",\"totalBrhCount\":\"4\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"What Young India Wants\",\"titleId\":\"310936\",\"titleType\":\"B\",\"authorName\":\"Chetan Bhagat\",\"totalCount\":\"271\",\"avlCount\":\"6\",\"totalBrhCount\":\"17\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":[{\"location\":\"IN-R1S1\",\"isAvailable\":\"true\"},{\"location\":\"NA-R1S2\",\"isAvailable\":\"true\"},{\"location\":\"IN-R1S1\",\"isAvailable\":\"true\"},{\"location\":\"IN-R1S1\",\"isAvailable\":\"true\"}]}},{\"titleBasic\":{\"title\":\"And Thereby Hangs A Tale\",\"titleId\":\"152410\",\"titleType\":\"B\",\"authorName\":\"Jeffrey Archer\",\"totalCount\":\"59\",\"avlCount\":\"5\",\"totalBrhCount\":\"8\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":[{\"location\":\"FI-R1S1\",\"isAvailable\":\"true\"},{\"location\":\"FI-R1S1\",\"isAvailable\":\"true\"},{\"location\":\"FI-R1S1\",\"isAvailable\":\"true\"},{\"location\":\"FI-R1S1\",\"isAvailable\":\"true\"},{\"location\":\"FI-R1S1\",\"isAvailable\":\"true\"}]}},{\"titleBasic\":{\"title\":\"FIVE POINT SOMEONE[PB]\",\"titleId\":\"193824\",\"titleType\":\"B\",\"authorName\":\"CHETAN BHGAT\",\"totalCount\":\"77\",\"avlCount\":\"0\",\"totalBrhCount\":\"0\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"7. ASTERIX AND THE BIG FIGHT\",\"titleId\":\"187025\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"50\",\"avlCount\":\"1\",\"totalBrhCount\":\"3\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}}},{\"titleBasic\":{\"title\":\"16. ASTERIX IN SWITZERLAND\",\"titleId\":\"186990\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"36\",\"avlCount\":\"1\",\"totalBrhCount\":\"2\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}}},{\"titleBasic\":{\"title\":\"8. ASTERIX IN BRITAIN\",\"titleId\":\"187026\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"45\",\"avlCount\":\"1\",\"totalBrhCount\":\"2\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":{\"location\":\"YR-R4S5\",\"isAvailable\":\"true\"}}},{\"titleBasic\":{\"title\":\"26. ASTERIX AND THE BLACK GOLD\",\"titleId\":\"187005\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"43\",\"avlCount\":\"1\",\"totalBrhCount\":\"2\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}}},{\"titleBasic\":{\"title\":\"12. ASTERIX AT THE OLYMPIC GAMES\",\"titleId\":\"186986\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"41\",\"avlCount\":\"0\",\"totalBrhCount\":\"2\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"33. ASTERIX AND THE FALLING SKY\",\"titleId\":\"187014\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"35\",\"avlCount\":\"0\",\"totalBrhCount\":\"1\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"THEODORE BOONE (HALF THE MAN, TWICE THE LAWYER)\",\"titleId\":\"187802\",\"titleType\":\"B\",\"authorName\":\"GRISHAM, JOHN\",\"totalCount\":\"112\",\"avlCount\":\"5\",\"totalBrhCount\":\"9\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":[{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"},{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"},{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"},{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"},{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"}]}},{\"titleBasic\":{\"title\":\"The Litigators\",\"titleId\":\"251906\",\"titleType\":\"B\",\"authorName\":\"Grisham, John\",\"totalCount\":\"128\",\"avlCount\":\"5\",\"totalBrhCount\":\"7\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":[{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"},{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"},{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"},{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"},{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"}]}},{\"titleBasic\":{\"title\":\"32. ASTERIX AND THE CLASS ACT\",\"titleId\":\"187013\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"37\",\"avlCount\":\"2\",\"totalBrhCount\":\"3\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}}},{\"titleBasic\":{\"title\":\"6. ASTERIX AND CLEOPATRA\",\"titleId\":\"187024\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"50\",\"avlCount\":\"2\",\"totalBrhCount\":\"2\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":[{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"},{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}]}},{\"titleBasic\":{\"title\":\"2. ASTERIX AND THE GOLDEN SICKLE\",\"titleId\":\"186996\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"38\",\"avlCount\":\"3\",\"totalBrhCount\":\"4\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":[{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"},{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}]}},{\"titleBasic\":{\"title\":\"17. THE MANSIONS OF THE GODS\",\"titleId\":\"186991\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"43\",\"avlCount\":\"1\",\"totalBrhCount\":\"1\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}}},{\"titleBasic\":{\"title\":\"22. ASTERIX AND THE GREAT CROSSING\",\"titleId\":\"187000\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"50\",\"avlCount\":\"1\",\"totalBrhCount\":\"3\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}}},{\"titleBasic\":{\"title\":\"3. ASTERIX AND THE GOTHS\",\"titleId\":\"187010\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"49\",\"avlCount\":\"1\",\"totalBrhCount\":\"2\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}}},{\"titleBasic\":{\"title\":\"24. ASTERIX IN BELGIUM\",\"titleId\":\"187003\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"33\",\"avlCount\":\"0\",\"totalBrhCount\":\"2\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"31. ASTERIX AND THE ACTRESS\",\"titleId\":\"187012\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"45\",\"avlCount\":\"0\",\"totalBrhCount\":\"2\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"Geronimo Stilton #02 The Curse Of The Cheese Pyramid\",\"titleId\":\"196654\",\"titleType\":\"B\",\"authorName\":\"GERONIMO STILTON\",\"totalCount\":\"20\",\"avlCount\":\"0\",\"totalBrhCount\":\"1\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"27. ASTERIX AND SON\",\"titleId\":\"187006\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"51\",\"avlCount\":\"0\",\"totalBrhCount\":\"1\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"Only Time Will Tell\",\"titleId\":\"173389\",\"titleType\":\"B\",\"authorName\":\"Jeffrey Archer\",\"totalCount\":\"67\",\"avlCount\":\"0\",\"totalBrhCount\":\"7\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"Steve Jobs: The Exclusive Biography\",\"titleId\":\"241987\",\"titleType\":\"B\",\"authorName\":\"Walter Isaacson\",\"totalCount\":\"7\",\"avlCount\":\"0\",\"totalBrhCount\":\"13\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"23. OBELIX AND CO\",\"titleId\":\"187001\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"44\",\"avlCount\":\"0\",\"totalBrhCount\":\"0\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"4. ASTERIX THE GLADIATOR\",\"titleId\":\"187019\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"42\",\"avlCount\":\"0\",\"totalBrhCount\":\"1\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"9. ASTERIX AND THE NORMANS\",\"titleId\":\"187455\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"59\",\"avlCount\":\"3\",\"totalBrhCount\":\"4\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":[{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"},{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"},{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}]}},{\"titleBasic\":{\"title\":\"20. ASTERIX IN CORSICA\",\"titleId\":\"186997\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"50\",\"avlCount\":\"2\",\"totalBrhCount\":\"3\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}}},{\"titleBasic\":{\"title\":\"5. ASTERIX AND THE BANQUET\",\"titleId\":\"187023\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"48\",\"avlCount\":\"1\",\"totalBrhCount\":\"1\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}}},{\"titleBasic\":{\"title\":\"10. ASTERIX THE LEGIONARY\",\"titleId\":\"186983\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"32\",\"avlCount\":\"0\",\"totalBrhCount\":\"1\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"30. ASTERIX AND OBELIX ALL AT SEA\",\"titleId\":\"187011\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"37\",\"avlCount\":\"0\",\"totalBrhCount\":\"1\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"Calico Joe\",\"titleId\":\"295253\",\"titleType\":\"B\",\"authorName\":\"John Grisham\",\"totalCount\":\"215\",\"avlCount\":\"5\",\"totalBrhCount\":\"7\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":[{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"},{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"},{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"},{\"location\":\"NA-R1S3\",\"isAvailable\":\"true\"},{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"}]}},{\"titleBasic\":{\"title\":\"ALCHEMIST\",\"titleId\":\"188239\",\"titleType\":\"B\",\"authorName\":\"Coelho, Paulo\",\"totalCount\":\"44\",\"avlCount\":\"0\",\"totalBrhCount\":\"8\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"1. ASTERIX THE GAUL\",\"titleId\":\"186982\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"29\",\"avlCount\":\"0\",\"totalBrhCount\":\"1\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"18. ASTERIX AND THE LAUREL WREATH\",\"titleId\":\"186992\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"54\",\"avlCount\":\"0\",\"totalBrhCount\":\"0\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"28. ASTERIX AND THE MAGIC CARPET\",\"titleId\":\"187007\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"41\",\"avlCount\":\"2\",\"totalBrhCount\":\"2\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":[{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"},{\"location\":\"FI-R4S4\",\"isAvailable\":\"true\"}]}},{\"titleBasic\":{\"title\":\"15. ASTERIX AND THE ROMAN AGENT\",\"titleId\":\"186989\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"41\",\"avlCount\":\"0\",\"totalBrhCount\":\"2\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"NEW MOON\",\"titleId\":\"188522\",\"titleType\":\"B\",\"authorName\":\"MEYER,STEPHENIE\",\"totalCount\":\"45\",\"avlCount\":\"0\",\"totalBrhCount\":\"1\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"21. ASTERIX AND CAESAR'S GIFT\",\"titleId\":\"186999\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"52\",\"avlCount\":\"2\",\"totalBrhCount\":\"4\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":[{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"},{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}]}},{\"titleBasic\":{\"title\":\"THE  MYSTERIOUS  CHEESE  THIEF\",\"titleId\":\"196134\",\"titleType\":\"B\",\"authorName\":\"Geronimo Stilton\",\"totalCount\":\"24\",\"avlCount\":\"0\",\"totalBrhCount\":\"2\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"19. ASTERIX AND THE SOOTHSAYER\",\"titleId\":\"186993\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"52\",\"avlCount\":\"0\",\"totalBrhCount\":\"1\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"14. ASTERIX IN SPAIN\",\"titleId\":\"186988\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"39\",\"avlCount\":\"1\",\"totalBrhCount\":\"2\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}}},{\"titleBasic\":{\"title\":\"11. ASTERIX AND THE CHIEFTAN'S SHIELD\",\"titleId\":\"186985\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"42\",\"avlCount\":\"3\",\"totalBrhCount\":\"3\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":[{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"},{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}]}},{\"titleBasic\":{\"title\":\"13. ASTERIX AND THE CAULDRON\",\"titleId\":\"186987\",\"titleType\":\"B\",\"authorName\":\"GOSCINNY, RENE & UDERZO, ALBERT\",\"totalCount\":\"50\",\"avlCount\":\"4\",\"totalBrhCount\":\"4\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\",\"LocationAvailability\":[{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"},{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"},{\"location\":\"YR-R10S3\",\"isAvailable\":\"true\"}]}},{\"titleBasic\":{\"title\":\"FAMOUS FIVE: 01: FIVE ON A TREASURE ISLAND (STANDARD)\",\"titleId\":\"187423\",\"titleType\":\"B\",\"authorName\":\"BLYTON, ENID\",\"totalCount\":\"27\",\"avlCount\":\"0\",\"totalBrhCount\":\"0\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"The Casual Vacancy\",\"titleId\":\"310326\",\"titleType\":\"B\",\"authorName\":\"J. K. Rowling\",\"totalCount\":\"41\",\"avlCount\":\"0\",\"totalBrhCount\":\"10\",\"language\":\"English\"},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}},{\"titleBasic\":{\"title\":\"GERONIMO STILTON: THEA STILTON AND THE CHERRY BLOSSOM ADV\",\"titleId\":\"205074\",\"titleType\":\"B\",\"authorName\":\"GERONIMO STILTON\",\"totalCount\":\"11\",\"avlCount\":\"0\",\"totalBrhCount\":\"2\",\"language\":[]},\"branchAvailability\":{\"branchName\":\"2 - JP Nagar\",\"branchID\":\"2\",\"branchShortName\":\"JPN\"}}]";
});

$app->get('/getMemberCardDetails', function(Request $request, Response $response){
    $request_data = $request->getQueryParams();
//    $branch_id = $_SESSION['branch_id'];
    $card = $request_data['card'];
    $result = curlFunction("closed_cards/closed_card_details/".$card.".xml");
    $xml = simplexml_load_string($result);
    $json = json_encode($xml);
    $final_array = json_decode($json, TRUE);
    echo json_encode($final_array);
});

$app->get('/getShelfLocation', function (Request $request, Response $response) {
//    $branchid = 2;
//    $result = curlFunction("branches/".$branchid."/shelf_locations.xml");
//    $xml = simplexml_load_string($result);
//    $json = json_encode($xml);
//    $final_array = json_decode($json,true);
////    print_r($final_array['shelf_locations']['shelf_location']);
//    echo json_encode($final_array['shelf_locations']);
    echo "{\"@attributes\":{\"type\":\"array\"},\"shelf_location\":[\"FI-R1S1\",\"FI-R1S2\",\"FI-R1S3\",\"FI-R1S4\",\"FI-R1S5\",\"FI-R1S6\",\"FI-R1S7\",\"FI-R2S1\",\"FI-R2S2\",\"FI-R2S3\",\"FI-R2S4\",\"FI-R2S5\",\"FI-R2S6\",\"FI-R2S7\",\"FI-R3S1\",\"FI-R3S2\",\"FI-R3S3\",\"FI-R3S4\",\"FI-R3S5\",\"FI-R3S6\",\"FI-R3S7\",\"FI-R4S1\",\"FI-R4S2\",\"FI-R4S3\",\"FI-R4S4\",\"FI-R4S5\",\"FI-R4S6\",\"FI-R4S7\",\"FI-R5S1\",\"FI-R5S2\",\"FI-R5S3\",\"FI-R5S4\",\"FI-R5S5\",\"FI-R5S6\",\"FI-R5S7\",\"FI-R6S1\",\"FI-R6S2\",\"FI-R6S3\",\"FI-R6S4\",\"FI-R6S5\",\"FI-R6S6\",\"FI-R6S7\",\"FI-R7S1\",\"FI-R7S2\",\"FI-R7S3\",\"FI-R7S4\",\"FI-R7S5\",\"FI-R7S6\",\"FI-R7S7\",\"FI-R8S1\",\"FI-R8S2\",\"FI-R8S3\",\"FI-R8S4\",\"FI-R8S5\",\"FI-R8S6\",\"FI-R8S7\",\"FI-R9S1\",\"FI-R9S2\",\"FI-R9S3\",\"FI-R9S4\",\"FI-R9S5\",\"FI-R9S6\",\"FI-R9S7\",\"FI-R10S1\",\"FI-R10S2\",\"FI-R10S3\",\"FI-R10S4\",\"FI-R10S5\",\"FI-R10S6\",\"FI-R10S7\",\"FI-R11S1\",\"FI-R11S2\",\"FI-R11S3\",\"FI-R11S4\",\"FI-R11S5\",\"FI-R11S6\",\"FI-R11S7\",\"FI-R12S1\",\"FI-R12S2\",\"FI-R12S3\",\"FI-R12S4\",\"FI-R12S5\",\"FI-R12S6\",\"FI-R12S7\",\"FI-R13S1\",\"FI-R13S2\",\"FI-R13S3\",\"FI-R13S4\",\"FI-R13S5\",\"FI-R13S6\",\"FI-R13S7\",\"FI-R14S1\",\"FI-R14S2\",\"FI-R14S3\",\"FI-R14S4\",\"FI-R14S5\",\"FI-R14S6\",\"FI-R14S7\",\"FI-R15S1\",\"FI-R15S2\",\"FI-R15S3\",\"FI-R15S4\",\"FI-R15S5\",\"FI-R15S6\",\"FI-R15S7\",\"FI-R16S1\",\"FI-R16S2\",\"FI-R16S3\",\"FI-R16S4\",\"FI-R16S5\",\"FI-R16S6\",\"FI-R16S7\",\"FI-R17S1\",\"FI-R17S2\",\"FI-R17S3\",\"FI-R17S4\",\"FI-R17S5\",\"FI-R17S6\",\"FI-R17S7\",\"IBT-R1S1\",\"IN-R1S1\",\"IN-R1S2\",\"IN-R1S3\",\"IN-R1S4\",\"IN-R1S5\",\"IN-R1S6\",\"IN-R1S7\",\"IN-R2S1\",\"IN-R2S2\",\"IN-R2S3\",\"IN-R2S4\",\"IN-R2S5\",\"IN-R2S6\",\"IN-R2S7\",\"IN-R3S1\",\"IN-R3S2\",\"IN-R3S3\",\"IN-R3S4\",\"IN-R3S5\",\"IN-R3S6\",\"IN-R3S7\",\"IN-R4S1\",\"IN-R4S2\",\"IN-R4S3\",\"IN-R4S4\",\"IN-R4S5\",\"IN-R4S6\",\"IN-R4S7\",\"IN-R5S1\",\"IN-R5S2\",\"IN-R5S3\",\"IN-R5S4\",\"IN-R5S5\",\"IN-R5S6\",\"IN-R5S7\",\"JR-R1S1\",\"JR-R1S2\",\"JR-R1S3\",\"JR-R1S4\",\"JR-R1S5\",\"JR-R1S6\",\"JR-R1S7\",\"NA-R1S1\",\"NA-R1S2\",\"NA-R1S3\",\"NA-R1S4\",\"NA-R1S5\",\"NA-R1S6\",\"NA-R1S7\",\"NF-R1S1\",\"NF-R1S2\",\"NF-R1S3\",\"NF-R1S4\",\"NF-R1S5\",\"NF-R1S6\",\"NF-R1S7\",\"NF-R2S1\",\"NF-R2S2\",\"NF-R2S3\",\"NF-R2S4\",\"NF-R2S5\",\"NF-R2S6\",\"NF-R2S7\",\"NF-R3S1\",\"NF-R3S2\",\"NF-R3S3\",\"NF-R3S4\",\"NF-R3S5\",\"NF-R3S6\",\"NF-R3S7\",\"NF-R4S1\",\"NF-R4S2\",\"NF-R4S3\",\"NF-R4S4\",\"NF-R4S5\",\"NF-R4S6\",\"NF-R4S7\",\"NF-R5S1\",\"NF-R5S2\",\"NF-R5S3\",\"NF-R5S4\",\"NF-R5S5\",\"NF-R5S6\",\"NF-R5S7\",\"NF-R6S1\",\"NF-R6S2\",\"NF-R6S3\",\"NF-R6S4\",\"NF-R6S5\",\"NF-R6S6\",\"NF-R6S7\",\"NF-R7S1\",\"NF-R7S2\",\"NF-R7S3\",\"NF-R7S4\",\"NF-R7S5\",\"NF-R7S6\",\"NF-R7S7\",\"NF-R8S1\",\"NF-R8S2\",\"NF-R8S3\",\"NF-R8S4\",\"NF-R8S5\",\"NF-R8S6\",\"NF-R8S7\",\"NF-R9S1\",\"NF-R9S2\",\"NF-R9S3\",\"NF-R9S4\",\"NF-R9S5\",\"NF-R9S6\",\"NF-R9S7\",\"NF-R10S1\",\"NF-R10S2\",\"NF-R10S3\",\"NF-R10S4\",\"NF-R10S5\",\"NF-R10S6\",\"NF-R10S7\",\"NF-R11S1\",\"NF-R11S2\",\"NF-R11S3\",\"NF-R11S4\",\"NF-R11S5\",\"NF-R11S6\",\"NF-R11S7\",\"NF-R12S1\",\"NF-R12S2\",\"NF-R12S3\",\"NF-R12S4\",\"NF-R12S5\",\"NF-R12S6\",\"NF-R12S7\",\"NF-R13S1\",\"NF-R13S2\",\"NF-R13S3\",\"NF-R13S4\",\"NF-R13S5\",\"NF-R13S6\",\"NF-R13S7\",\"NF-R14S1\",\"NF-R14S2\",\"NF-R14S3\",\"NF-R14S4\",\"NF-R14S5\",\"NF-R14S6\",\"NF-R14S7\",\"NF-R15S1\",\"NF-R15S2\",\"NF-R15S3\",\"NF-R15S4\",\"NF-R15S5\",\"NF-R15S6\",\"NF-R15S7\",\"NF-R16S1\",\"NF-R16S2\",\"NF-R16S3\",\"NF-R16S4\",\"NF-R16S5\",\"NF-R16S6\",\"NF-R16S7\",\"NF-R25S1\",\"NF-R25S2\",\"NF-R25S3\",\"NF-R25S4\",\"NF-R25S5\",\"NF-R25S6\",\"NF-R25S7\",\"NF-R30S1\",\"NF-R30S2\",\"NF-R30S3\",\"NF-R30S4\",\"NF-R30S5\",\"REKA-R1S1\",\"REKA-R1S2\",\"REKA-R1S3\",\"REKA-R1S4\",\"REKA-R1S5\",\"REKA-R1S6\",\"REKA-R1S7\",\"REKA-R2S1\",\"REKA-R2S2\",\"REKA-R2S3\",\"REKA-R2S4\",\"REKA-R2S5\",\"REKA-R2S6\",\"REKA-R2S7\",\"RE-R3S1\",\"RE-R3S2\",\"RE-R3S3\",\"RE-R3S4\",\"RE-R3S5\",\"RE-R3S6\",\"RE-R3S7\",\"RETA-R4S1\",\"RETA-R4S2\",\"RETA-R4S3\",\"RETA-R4S4\",\"RETA-R4S5\",\"RETA-R4S6\",\"RETA-R4S7\",\"RETA-R5S1\",\"RETA-R5S2\",\"RETA-R5S3\",\"RETA-R5S4\",\"RETA-R5S5\",\"RETA-R5S6\",\"RETA-R5S7\",\"REMA-R6S1\",\"REMA-R6S2\",\"REMA-R6S3\",\"REMA-R6S4\",\"REMA-R6S5\",\"REMA-R6S6\",\"REMA-R6S7\",\"RE-R7S1\",\"RE-R7S2\",\"RE-R7S3\",\"RE-R7S4\",\"RE-R7S5\",\"RE-R7S6\",\"RE-R7S7\",\"RE-R8S1\",\"RE-R8S2\",\"RE-R8S3\",\"RE-R8S4\",\"RE-R8S5\",\"RE-R8S6\",\"RE-R8S7\",\"SP-R1S1\",\"SP-R1S2\",\"SP-R1S3\",\"SP-R1S4\",\"SP-R1S5\",\"SP-R1S6\",\"SP-R1S7\",\"SP-R2S1\",\"SP-R2S2\",\"SP-R2S3\",\"SP-R2S4\",\"SP-R2S5\",\"SP-R2S6\",\"SP-R2S7\",\"SP-R3S1\",\"SP-R3S2\",\"SP-R3S3\",\"SP-R3S4\",\"SP-R3S5\",\"SP-R3S6\",\"SP-R3S7\",\"YR-R1S1\",\"YR-R1S2\",\"YR-R1S3\",\"YR-R1S4\",\"YR-R1S5\",\"YR-R1S6\",\"YR-R1S7\",\"YR-R2S1\",\"YR-R2S2\",\"YR-R2S3\",\"YR-R2S4\",\"YR-R2S5\",\"YR-R2S6\",\"YR-R2S7\",\"YR-R3S1\",\"YR-R3S2\",\"YR-R3S3\",\"YR-R3S4\",\"YR-R3S5\",\"YR-R3S6\",\"YR-R3S7\",\"YR-R4S1\",\"YR-R4S2\",\"YR-R4S3\",\"YR-R4S4\",\"YR-R4S5\",\"YR-R4S6\",\"YR-R4S7\",\"YR-R5S1\",\"YR-R5S2\",\"YR-R5S3\",\"YR-R5S4\",\"YR-R5S5\",\"YR-R5S6\",\"YR-R5S7\",\"YR-R6S1\",\"YR-R6S2\",\"YR-R6S3\",\"YR-R6S4\",\"YR-R6S5\",\"YR-R6S6\",\"YR-R6S7\",\"YR-R7S1\",\"YR-R7S2\",\"YR-R7S3\",\"YR-R7S4\",\"YR-R7S5\",\"YR-R7S6\",\"YR-R7S7\",\"YR-R8S1\",\"YR-R8S2\",\"YR-R8S3\",\"YR-R8S4\",\"YR-R8S5\",\"YR-R8S6\",\"YR-R8S7\",\"YR-R9S1\",\"YR-R9S2\",\"YR-R9S3\",\"YR-R9S4\",\"YR-R9S5\",\"YR-R9S6\",\"YR-R9S7\",\"YR-R10S1\",\"YR-R10S2\",\"YR-R10S3\",\"YR-R10S4\",\"YR-R10S5\",\"YR-R10S6\",\"YR-R10S7\",\"YR-R11S1\",\"YR-R11S2\",\"YR-R11S3\",\"YR-R11S4\",\"YR-R11S5\",\"YR-R11S6\",\"YR-R11S7\",\"YR-R12S1\",\"YR-R12S2\",\"YR-R12S3\",\"YR-R12S4\",\"YR-R12S5\",\"YR-R12S6\",\"YR-R12S7\",\"YR-R13S1\",\"YR-R13S2\",\"YR-R13S3\",\"YR-R13S4\",\"YR-R13S5\",\"YR-R13S6\",\"YR-R13S7\",\"YR-R14S1\",\"YR-R14S2\",\"YR-R14S3\",\"YR-R14S4\",\"YR-R14S5\",\"YR-R14S6\",\"YR-R14S7\",\"YR-R15S1\",\"YR-R15S2\",\"YR-R15S3\",\"YR-R15S4\",\"YR-R15S5\",\"YR-R15S6\",\"YR-R15S7\",\"YR-R16S1\",\"YR-R16S2\",\"YR-R16S3\",\"YR-R16S4\",\"YR-R16S5\",\"YR-R16S6\",\"YR-R16S7\",\"YR-R17S1\",\"YR-R17S2\",\"YR-R17S3\",\"YR-R17S4\",\"YR-R17S5\",\"YR-R17S6\",\"YR-R17S7\",\"YR-R18S1\",\"YR-R18S2\",\"YR-R18S3\",\"YR-R18S4\",\"YR-R18S5\",\"YR-R18S6\",\"YR-R18S7\"]}";
});

$app->post('/updateShelfLocation', function (Request $request, Response $response) {
    $branchid = 2;
    $request_data = $request->getParsedBody();
    $shelf_loc = $request_data['location'];
    $books = $request_data['books'];
    $books_array = explode(',',rtrim($books,','));
    $books_array_build = '';
    foreach($books_array as $arr){
        $books_array_build .= '<book-no>'.$arr.'</book-no>';
    }
    $xml = '<bulk_books><branch_id>'.$branchid.'</branch_id><book-nos type=\'array\'>'.$books_array_build.'</book-nos><shelf_location>'.$shelf_loc.'</shelf_location></bulk_books>';
//    echo $xml;die;
    $result = curlFunctionXML("books/update_shelf_location.xml", $xml);
    $xml = simplexml_load_string($result);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo json_encode($final_array['book']);
});

$app->get('/getSortBook', function (Request $request, Response $response) {
    $request_data = $request->getQueryParams();
    $book = $request_data['book'];
    $branchid = 2;

    $httpHeader = serverHeader();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://ops.staging.justbooksclc.com/sort_book/$book/$branchid.xml");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    $raw_data = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($raw_data);
    $json = json_encode($xml);
    $final_array = json_decode($json,true);
    echo json_encode($final_array['jb-books']['jb-book']);
});

$app->get('/', function (Request $request, Response $response) {
    $response = $this->view->render($response, 'main_home.mustache');
    return $response;
});

$app->get('/getBookshelfDetails', function(Request $request, Response $response){
    $con = $this->db;
    $membership_no = trim($_SESSION['member_card']);
    $past_read = [];
    $cur_reading = [];
    $result = curlFunction("member_plans/wish_list_items/".$membership_no.".xml");
    $xml = simplexml_load_string($result);
    $json = json_encode($xml);
    $final_data['wish_list'] = json_decode($json,TRUE);

    $query = "select title,'http://cdn2.justbooksclc.com/medium/'||isbn||'.jpg' img from rentals r join titles t on r.legacy_title_id=t.id
      where membership_no='$membership_no'";
    $result_current_reading = oci_parse($con, $query);
    oci_execute($result_current_reading);
    while($row = oci_fetch_assoc($result_current_reading)){
        $cur_reading[] = $row;
    }
    $final_data['current_reading'] = $cur_reading;

    $query = "select title,'http://cdn2.justbooksclc.com/medium/'||isbn||'.jpg' img from returns r join titles t on r.legacy_title_id=t.id
        where membership_no='$membership_no' group by title,isbn";
    $result_past_read = oci_parse($con, $query);
    oci_execute($result_past_read);
    while($row = oci_fetch_assoc($result_past_read)){
        $past_read[] = $row;
    }
    $final_data['past_read'] = $past_read;

    echo json_encode($final_data,TRUE);
});

$app->get('/getIssueBook', function(Request $request, Response $response){
    $parameters = $request->getQueryParams();
    $card = trim($_SESSION['member_card']);
//    echo $parameters['bookno'].'---'.$card;die;
    $result = curlFunction("books/".$parameters['bookno']."/details_for_issue.xml?membership_no=$card");
    $xml = simplexml_load_string($result);
    $json = json_encode($xml);
    echo json_encode(json_decode($json,TRUE));
});

$app->get('/addWishlist', function(Request $request, Response $response){
    $parameters = $request->getQueryParams();
    $titleid = $parameters['titleid'];
    $membership_no = $_SESSION['member_card'];
    $kioskid = $_SESSION['kioskid'];
    $xml = "<wish-list-item><membership-no>$membership_no</membership-no><title-id>$titleid</title-id><created-in>$kioskid</created-in><reading-priority>1</reading-priority></wish-list-item>";
    $result = curlFunctionXML("memberships/$membership_no/wish_list_items.xml",$xml);
    $xml_response = simplexml_load_string($result);
    $json = json_encode($xml_response);
    echo json_encode(json_decode($json,TRUE));
});

$app->get('/placeRequestOrder', function(Request $request, Response $response){
    $parameters = $request->getQueryParams();
    $titleid = $parameters['titleid'];
    $membership_no = $_SESSION['member_card'];
    $member_branchid = $_SESSION['member_branch_id'];
    $kioskid = $_SESSION['kioskid'];
    $xml = "<delivery-order><membership-no>$membership_no</membership-no><title-id>$titleid</title-id><branch-id>$member_branchid</branch-id><created-by>1000</created-by></delivery-order>";
    $result = curlFunctionXML("delivery_orders.xml",$xml);
    $xml_response = simplexml_load_string($result);
    $json = json_encode($xml_response);
    echo json_encode(json_decode($json,TRUE));
});

$app->get('/getIssueBookResponse', function(Request $request, Response $response){
    $membership_no = 'M068234';
    $booknumber = 'B0777797';
    $xml = '<bulk-rental><membership-no>'.$membership_no.'</membership-no><book-numbers type="array"><book-number>'.$booknumber.'</book-number></book-numbers><username>"TWHITEFIELD1"</username><branch-id type=\'integer\'>1</branch-id></bulk-rental>';
    $result = curlFunctionXML("bulk_rentals.xml",$xml);
    $xml_response = simplexml_load_string($result);
    $json = json_encode($xml_response);
    echo json_encode(json_decode($json,TRUE));
});

$app->get('/getReturnBook', function(Request $request, Response $response){
    $membership_no = 'M068234';
    $booknumber = 'B0777797';
    $result = curlFunction("books/".$booknumber."/details_for_return.xml?membership_no=$membership_no");
    $xml = simplexml_load_string($result);
    $json = json_encode($xml);
    echo json_encode(json_decode($json,TRUE));
});

$app->get('/getReturnBookResponse', function(Request $request, Response $response){
    $membership_no = trim($_SESSION['member_card']);
    $booknumber = 'B0777797';
    $xml = '<bulk-return><membership-no>'.$membership_no.'</membership-no><book-numbers type="array"><book-number>'.$booknumber.'</book-number></book-numbers><username>"TWHITEFIELD1"</username><branch-id type=\'integer\'>1</branch-id></bulk-return>';
    $result = curlFunctionXML("bulk_returns.xml",$xml);
    $xml_response = simplexml_load_string($result);
    $json = json_encode($xml_response);
    echo json_encode(json_decode($json,TRUE));
});

$app->get('/getSubscription', function(Request $request, Response $response){
    $con = $this->db;
    $membership_no = trim($_SESSION['member_card']);
    $subscription = [];
    $query = "select p.name,bulk_payment,mp.expiry_date from plans p join member_plans mp on mp.plan_id=p.id where membership_no='$membership_no'";
    $result_subscription = oci_parse($con, $query);
    oci_execute($result_subscription);
    while($row = oci_fetch_assoc($result_subscription)){
        $subscription[] = $row;
    }

    echo json_encode($subscription);
});

$app->get('/getProfile', function(Request $request, Response $response){
    $con = $this->db;
    $membership_no = trim($_SESSION['member_card']);
    $profile = [];
    $query = "select email_id,p.first_name,register_time,phone,address1 from member_profiles p join member_plans mp
      on mp.member_profile_id=p.id where membership_no='$membership_no'";
    $result_profile = oci_parse($con, $query);
    oci_execute($result_profile);
    while($row = oci_fetch_assoc($result_profile)){
        $profile[] = $row;
    }

    echo json_encode($profile);
});

$app->get('/getMostReadBranch', function(Request $request, Response $response){
    $branchid = 2;
    $result = curlFunction("api/featured_analytics.xml?branch_id=$branchid&ananlytics_type=NR");
    $xml = simplexml_load_string($result);
    $json = json_encode($xml);
    $final_array = json_decode($json, TRUE);
//    print_r($final_array);die;
    echo json_encode($final_array['Featured-analytic']);
});

$app->get('/detectCardType', function(Request $request, Response $response){
    $data = $request->getQueryParams();
    $card = $data['cardno'];
    $result = curlFunction("member_plans/get_card_details/".$card.".xml");
    $xml = simplexml_load_string($result);
    $json = json_encode($xml);
    echo json_encode(json_decode($json,TRUE));
});

$app->post('/getMemberDetails', function(Request $request, Response $response){
    $data = $request->getParsedBody();
    $card = $data['cardno'];
//    echo trim($card);die;
    $result = curlFunction("member_plans/get_member_detail/".trim($card).".xml");
    $xml = simplexml_load_string($result);
    $json = json_encode($xml);
    $final_array = json_decode($json, TRUE);
    $_SESSION['member_branch_id'] = trim($final_array['member-branch-id']);
    $_SESSION['member_name'] = trim($final_array['name']);
    $_SESSION['member_card'] = trim($card);
    echo json_encode($final_array);
});

$app->run();

?>
