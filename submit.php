<?php 
header ("Access-Control-Allow-Origin: *");
header ("Content-Type: application/json");

$emailPattern = "/([\w\-]+\@[\w\-]+\.[\w\-]+)/";

$to = utf8_decode($_POST["to"]);
if (!preg_match ($emailPattern, $to))
	die (json_encode(["code" => 1,
		"result" => "Invalid recipient",
		"more" => "Please verify your recipient email."]));

$from = utf8_decode($_POST["from"]);
if (!preg_match ($emailPattern, $from))
	die (json_encode(["code" => 1,
		"result" => "Invalid sender",
		"more" => "Please verify your sender email."]));

$subject = utf8_decode($_POST["subj"]);
if (strlen($subject) != strlen($_POST["subj"]))
	$subject = "=?ISO-8859-1?Q?".$subject."?=";

$id = md5(uniqid(time(), true));

$header["From"] = $header["Reply-To"] = $from; 
$header["Content-Transfer-Encoding"] = "quoted-printable";
$header["Mime-Version"] = "1.0";
$header["Content-Type"] = "multipart/mixed; boundary=\"$id\"";

if (file_exists("email.html")) {
	$html = trim(implode("\n", file("email.html")));
	if (strlen($_POST["comments"]))
		$html = str_replace("<!--{COMMENT}-->", nl2br(htmlentities($_POST["comments"])), $html);
	$html = str_replace("<!--{URL}-->", $_POST["url"], $html);
	$mess = "--$id\nContent-Transfer-Encoding: quoted-printable\nContent-Type: text/html; charset=utf-8\n\n";
	$mess .= quoted_printable_encode(str_replace("{IMAGEDATA}", $_POST["image"], $html))."\n\n";
	$mess .= "--$id--";
}
else {
	$mess = "--$id\nContent-Transfer-Encoding: quoted-printable\nContent-Type: text/plain; charset=utf-8\n\n";
	if (strlen($_POST["comments"]))
		$mess .= quoted_printable_encode($_POST["comments"])."\n\n";
	$mess .= quoted_printable_encode($_POST["url"])."\n\n";
	$mess .= "--$id\nContent-Disposition: attachment; filename=\"image.png\"\nContent-Type: image/png; name=\"image.png\"\nContent-Transfer-Encoding: base64\n\n";
	$mess .= chunk_split ($_POST["image"])."\n\n";
	$mess .= "--$id--";
}

$head = "";
foreach ($header as $k => $v)
	$head .= "\n".$k.":".$v;
$head = substr($head, 1);

if ($_POST["copyMe"])
	mail ($from, $subject, $mess, $head);

if (mail ($to, $subject, $mess, $head))
	die (json_encode(["code" => 0]));
else
	die (json_encode(["code" => 1,
		"result" => "Error sending email",
		"more" => "Something went wrong with this email."]));
?>