<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title></title>
  <style>
    @page {
      margin: 5px;
    }

    body {
      margin: 0px;
      font-family: 'arial', sans-serif;
    }

    .chinese {
      line-height: 10px;
      font-family: 'yahei', 'arial', sans-serif;
    }

    table,
    tr {
      border-collapse: collapse;
      /*border-style: hidden solid hidden solid;
      border-right: 1px solid black;
       border: 1px solid black;*/
    }

    th,
    td {
      /* border: 1px solid black;*/
      font-size: 13px;
    }

    td {
      padding-left: 7px;
    }

    ul {
      padding-left: 20px;
      margin-top: 0;
    }

    .table_detail_value {
      padding-left: 7px;
      padding-right: 7px;
      padding-top: 2px;
      padding-bottom: 2px;
      width: 10%;
      border-left: none;
      border-right: none;
      text-align: center;
    }

    .thin_bottom {
      border-bottom: 1px solid #d9d9d9 !important;
    }

    .thin_top {
      border-top: 1px solid #d9d9d9 !important;
    }

    .table_detail_key {
      width: 20%;
      padding-left: 7px;
      padding-right: 7px;
      padding-top: 2px;
      padding-bottom: 2px;
      font-weight: bold;
    }

    .editted {
      background-color: #ffc0bc;
    }

    .double {
      color: #7030a0;
      padding-top: 2px;
      padding-bottom: 2px;
      padding-left: 5px;
      padding-right: 5px;
      text-decoration: underline;
      text-decoration-color: #7030a0;
    }

    .can_drive_lorry {
      color: #208ff0;
      padding-top: 2px;
      padding-bottom: 2px;
      padding-left: 5px;
      padding-right: 5px;
    }

    .double.can_drive_lorry {
      color: #208ff0 !important;
      text-decoration: underline !important;
      text-decoration-color: #7030a0 !important;
    }

    h4 {
      padding: 0;
      margin: 0;
    }
  </style>
</head>

<body>
  <table style="border: none;margin:0;">
    <tr style="border: none;">
      <th style="border: none; width: 5%;text-align:left;"><img
          src="https://haohan.at-eases.com/img/brand_logo_smaller.png" alt="brand_logo"
          style="display:inline-block;max-width:100%;">
      </th>
      <th style="border: none;width:95%"></th>
    </tr>
    <tr style="border: none;">
      <td style="border: none;width: 35%;">
        <h4>Daily Team & Job Arrangement {{$draft}}</h4>
        <h4 class="{{$updated_by_chinese}}">Last updated by: {{$updated_by}}</h4>
        <h4>Last updated at: {{$last_updated_at}}</h4>
        <br>
      </td>
      <td style="border: none;width:65%"></td>
    </tr>
  </table>
  <table style="width: 100%;border: 1px solid black;">
    <tr style="background-color: #e1efda; text-align: center; border: 1px solid black;">
      <td style="width: 32%; padding-top: 8px; padding-bottom: 8px;text-align: center;border:1px solid #000000;">
        Date: {{$job_sheet_date}}</td>
      <td style="width: 17%; padding-top: 8px; padding-bottom: 8px;text-align: center;border:1px solid #000000;">Team:
        {{$total_assigned_staff}}/{{$total_staff}}
      </td>
      <td style="width: 17%; padding-top: 8px; padding-bottom: 8px;text-align: center;border:1px solid #000000;">Repeat:
        {{$total_repeat}}
      </td>
      <td style="width: 17%; padding-top: 8px; padding-bottom: 8px;text-align: center;border:1px solid #000000;">
        Vehicle:
        {{$total_asisgned_vehicle}}/{{$total_vehicle}}
      </td>
      <td style="width: 17%; padding-top: 8px; padding-bottom: 8px;text-align: center;border:1px solid #000000;">Rental:
        {{$total_rental}}
      </td>
    </tr>
  </table>
  <br>
  <?=$team_table_list_string?>
  <?=$leave_table_string?>
</body>

</html>