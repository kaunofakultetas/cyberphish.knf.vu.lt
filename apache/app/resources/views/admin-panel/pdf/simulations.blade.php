<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Simulations</title>
    <style>
        body { font-family:Arial; border:0; font-size:12px; } 
        td { padding: 4px; }
    </style>
  </head>
  <body style="padding:10px;">
 <center> 
 <table border="1" style="width:800px">
 
 	@foreach($simulations as $v)
 	
 	     <tr>
 				<td colspan="4" style="text-align:right; font-size:16px; font-weight:bold;"> Language: {{ $v['lang'] }} </td>
 		 </tr>
 	
 		@foreach($v['simulations'] as $scenario)
 		
 			<tr>
 				<td colspan="4"><b> Scenario: <br> {{ $scenario['descr'] }} </b></td>
 			</tr>
 			
 				<tr>
 					<td width="30">Level</td><td width="30">Points</td> <td>Situation</td> <td>Feedback</td>
 				</tr>
 			
     			@foreach($scenario['option'] as $option)
     			
     			<tr>
 					<td>{{ $option['level'] }}</td><td>{{ $option['points'] }}</td> <td> {{ $option['situation'] }} </td><td> {{ $option['feedback'] }} </td>
 				</tr>
     			
     			@endforeach
 			
 			<tr>
 				<td valign="top" width="33%" colspan="2">
 					Goal: {{ $scenario['goal'] ?? '' }} <br>
 					Source: {{ $scenario['source'] ?? '' }} <br>
 					Actors: {{ $scenario['actors'] ?? '' }} <br>
 					Type: {{ $scenario['choose_type'] ?? '' }} <br>
 					Attack type: {{ $scenario['attack_type'] ?? '' }} <br><br> 
 				
 				</td>
 				<td valign="top">
 				Categories: <br><br>  
 				
 				 
 				
 				  @if(isset($v['categories']) && count($v['categories'])>0)
 					@foreach($v['categories'] as $k => $cat)
 					 
 						  {{ $cat['name'] ?? '' }}
 						  <br>
 				
 					@endforeach
 				  @endif
 				</td>
 				<td valign="top" width="33%">
 				Attributes: <br><br>
 				
 				@if(isset($v['attributes']) && count($v['attributes'])>0)
 					@foreach($v['attributes'] as $attributes)
 					
 					{{ $attributes['name'] ?? '' }}
 					  <br>
 				
 					@endforeach
 				  @endif
 				
 				</td>
 			</tr>
 		
 		@endforeach
 	
 	@endforeach
 
 </table>
 </center>
  </body>
</html>