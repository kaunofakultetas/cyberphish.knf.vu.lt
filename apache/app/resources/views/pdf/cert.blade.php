<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Cert</title>
  <link href="/static/css/fonts.css" rel="stylesheet">
  <style>
      @page { margin: 0px; }
      body { 
          color: #fff !important; 
          text-align: center;
          background-image: url({{ $backgroundPath }});
          background-position: center;
          background-repeat: no-repeat;
          background-size: 100% 100%;
      }
      h1 { padding:0px; margin:0px; font-family: 'Poppins'; font-size: 68px; line-height:30px;}
      h2 { padding:0px; margin:0px; font-family: 'Poppins'; font-size: 35px; font-weight:500; line-height:30px;}
      p { padding:0px; margin:0px; font-family: 'Poppins'; font-size: 24px; font-weight:200; }
      #name { font-family: 'Parisienne', cursive; font-size: 62px; color:#000!important; margin-top:320px; }
      .signature { float:left; display:inline-block; width:260px; margin-left:195px; font-family: 'Poppins'; font-size: 14px; font-weight:200; line-height:12px;}
      .duration { float:left; display:inline-block; width:130px; margin-left:180px; font-family: 'Poppins'; font-size: 14px; font-weight:200; margin-top:85px; line-height:20px;}
      .date { display:inline-block; font-family: 'Poppins'; font-size: 18px; font-weight:200; margin-top:150px; line-height:20px; color:#000!important; float:right; margin-right:310px;}
      .duration span { font-weight:500; }
      .date span { font-weight:500; }
      p span { font-weight:500; }
  </style>
</head>
<body>
  <div id="name">{{ $fullname }}</div>
  <div class="date"><span>{{ $date }}</span></div>
</body>
</body>
</html>