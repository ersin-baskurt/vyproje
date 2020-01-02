$(document).ready(function(){
	$('form').submit(function(e){
		e.preventDefault();
		$('#sonucListesi').empty();
		var aranacakKelime = $('#araInput').val();
		if (aranacakKelime.length < 2){
			$('#sonucListesi').html('<p style="color:red;">Lütfen aramak için en az iki harf giriniz.</p>');
			return false;
		}

		$('#sonucListesi').html('<p>Lütfen bekleyin... Dosyalar içinde aranıyor.</p>');
		
		$.ajax({
			type: 'GET',
			url: 'ara.php',
			data: 'ara='+aranacakKelime,
			success: function(sonuc) {
				$('#sonucListesi').empty();

				var data = JSON.parse(sonuc);

				$.each( data, function( key, value ) {
				  	var tam_eslesmeler = '';
				  	var benzer_eslesmeler = '';

				  	for (var i = 0; i < data[key].length; i++) {
			    		var bulunan = data[key][i].split('|');
			    		if (bulunan[0].toLowerCase().trim() == aranacakKelime.toLowerCase().trim()){
			    			tam_eslesmeler += '<li>'+bulunan[0]+" - "+bulunan[1]+" - <i>"+bulunan[2].substring(0, 5)+' msn</i></li>';
			    		}
			    		else{
			    			benzer_eslesmeler += '<li>'+bulunan[0]+" - "+bulunan[1]+" - <i>"+bulunan[2].substring(0, 5)+' msn</i></li>';
			    		}
			    	}

			    	if ( data[key].length < 1 ){
			    		tam_eslesmeler = '<li>Veri bulunamadı.</li>';
			    		benzer_eslesmeler = '<li>Benzer sonuçlar bulunamadı.</li>';
			    	}



				  	var aranan_dosya = '<div class="sonuclar">'+
												'<h3>Şu dosyada bulunan sonuçlar: ('+key+')</h3>'+
												'<div class="satir">'+
													'<div class="sutun yarim-sutun">'+
														'<p>Tam eşleşmeler:</p>'+
														'<ul>'+tam_eslesmeler+'</ul>'+
													'</div>'+
													'<div class="sutun yarim-sutun">'+
														'<p>Bunu mu demek istediniz? (Benzer eşleşmeler)</p>'+
														'<ul>'+benzer_eslesmeler+'</ul>'+
													'</div>'+
												'</div>'+
											'</div>';
					$('#sonucListesi').append(aranan_dosya);
				});

			}
		});
	});

});