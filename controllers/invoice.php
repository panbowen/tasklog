<?php

require_once("./libs/dompdf/dompdf_config.inc.php");

$id = $_GET["id"];
$invoice = query("SELECT DATE_FORMAT(invoices.start_date, '%c/%e/%Y') AS startDate, DATE_FORMAT(invoices.end_date, '%c/%e/%Y') AS endDate, projects.name AS project, projects.bill_to_name AS name, projects.bill_to_address AS address, projects.bill_to_department AS department, projects.bill_to_campus AS campus, CONCAT('$', FORMAT(invoices.rate, 2)) AS rate, SUM(logs.hours) AS hours, CONCAT('$', FORMAT(SUM(logs.hours) * invoices.rate, 2)) AS total, invoices.description AS description, MAX(logs.date) AS lastDate
  FROM invoices JOIN projects ON invoices.project_id = projects.id JOIN logs ON invoices.project_id=logs.project_id AND logs.date >= invoices.start_date AND logs.date <= invoices.end_date
  WHERE invoices.id=:id
  ", [
    ":id" => $id
  ]);

$dompdf = new DOMPDF();
$dompdf -> load_html(mustache("invoice", $invoice[0]));
$dompdf -> render();
$dompdf -> stream("invoice.pdf", ["Attachment" => 0]);