<?php

	
	// arama kutusuna bişey yazıp arandığında aşağıdaki kod buloğu na istek gelir
	// eğer bir arama isteği geldiyse bunu alıp bir değişkene aktardık.
	// değişken adı $aranacakKelime
	if(isset($_GET['ara'])) {
		$aranacakKelime = $_GET['ara'];


	   	// aranacak kelime geldiyse ilk adım olarak dosyalar klasöründeki tüm dosyaları bulalım.
	   	// bulunan her bir dosya için dosyadaAra metodunu çağırıp içinde arama yapıyoruz
	   	$bulunanSonuclar = [];

		// dosyalar kalsörünün içini okur ve içindeki tüm dosyaları geri döner
		$files = scandir('dosyalar/');
		foreach ($files as $key => $value) {
			if ($files[$key] !== "." && $files[$key] !== ".."){
				$bulunanSonuclar[$files[$key]] = dosyadaAra($files[$key], $aranacakKelime);
			}

		}

		// bulunan sonuçları da bir json objesinde topladık istek yapılan siteye geri döndürüyoruz
		// bir diğer deyişle ekrana yazdırıyoruz.
		echo json_encode($bulunanSonuclar);

	}





	function dosyadaAra($dosyaAdi, $kelime){
		//gelen dosya adını nokta (.) ile parçalayıp sondaki parça bize uzantıyı veriyor.
		$dosya_turu = explode(".", $dosyaAdi);
		$dosya_uzanti = $dosya_turu[count($dosya_turu)-1];

		$dosya_icerik;

		// ardından uzantı hangi türdeyse o dosyayı okuyup içindeki metinlerde arama yaptıracağız
		// hangi türdeyse o dosyayı okuyup arama yapıcak metodu çağırıyoruz
		switch ($dosya_uzanti) {
			case 'txt':
				$dosya_icerik = txtDosyaOku($dosyaAdi, $kelime);
				break;
			case 'docx':
				$dosya_icerik = docxDosyaOku($dosyaAdi, $kelime);
				break;
			case 'pdfx':
				$dosya_icerik = pdfDosyaOku($dosyaAdi, $kelime);
				break;
			case 'html':
				$dosya_icerik = htmlDosyaOku($dosyaAdi, $kelime);
				break;
			
			default:
				break;
		}


		// en son bulunanlar bir önceki 18. satırdaki yere geri döndürüyoruz
		// orada da tüm dönenleri toplayıp siteye döndürüyoruz.
		return $dosya_icerik;
	}




	// txt dosyasının içeriğini okuyup ardından içeriğini ve aranacak kelimeyi asıl arama yapacak metodumuz olan 
	// boyerMooreArama() fonksiyonuna gönderiyoruz. Burdan gelen bulunan sonuçları da fonksiyonun çağrıldığı yere geri döüyoruz.
	// bu işlemleri her bir dosya türü için ayrı ayrı dosya okuma methodları var
	// çünkü php her dosyayı aynı şekilde okumaz.
	// txt okumak basit iken word dosyasını okumak daha karmaşıktır. Özel kütüphaneleri kullanmak gerek.
	// çünkü word dosyası txt gibi okunduğunda unicode karakterlerden oluşan bir metin dönüyor. Bu metni düzenlemek gerek
	function txtDosyaOku($dosyaAdi, $kelime){
		$dosya = fopen('dosyalar/'.$dosyaAdi, 'r');
		$icerik = fread($dosya, filesize('dosyalar/'.$dosyaAdi));
		fclose($dosya);
		return boyerMooreArama($icerik, $kelime);
	}



	// docx dosyasının içeriğini okuyup ardından içeriğini ve aranacak kelimeyi asıl arama yapacak metodumuz olan 
	// boyerMooreArama() fonksiyonuna gönderiyoruz. Burdan gelen bulunan sonuçları da fonksiyonun çağrıldığı yere geri döüyoruz.
	function docxDosyaOku($dosyaAdi, $kelime){
        $word_temiz_icerik = '';
	    $okunan_word_icerigi = '';

	    if(!$dosyaAdi || !file_exists('dosyalar/'.$dosyaAdi)) 
	    	return false;

	    $zip = zip_open('dosyalar/'.$dosyaAdi);
	    if (!$zip || is_numeric($zip)) return false;

	    while ($zip_entry = zip_read($zip)) {

	        if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

	        if (zip_entry_name($zip_entry) != "word/document.xml") continue;

	        $okunan_word_icerigi .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

	        zip_entry_close($zip_entry);
	    }
	    zip_close($zip);      
	    $okunan_word_icerigi = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $okunan_word_icerigi);
	    $okunan_word_icerigi = str_replace('</w:r></w:p>', "\r\n", $okunan_word_icerigi);
	    $word_temiz_icerik = strip_tags($okunan_word_icerigi);

	    return boyerMooreArama($word_temiz_icerik, $kelime);
	}



	//pdf dosyasının içeriğini okuyup ardından içeriğini ve aranacak kelimeyi asıl arama yapacak metodumuz olan 
	// boyerMooreArama() fonksiyonuna gönderiyoruz. Burdan gelen bulunan sonuçları da fonksiyonun çağrıldığı yere geri döüyoruz.

	// bu fonksiyon düzgün çalışmıyor. Çünkü aynı word dosyasındaki gibi okunan dosya düz txt formatında değil.
	// çalışması için özel pdf reader php kütüphaneleri kullanmak gerek
	// düzgün çalışan bir kütüphaneye ihtiyaç var.
	function pdfDosyaOku($dosyaAdi, $kelime){
		$dosya = fopen('dosyalar/'.$dosyaAdi, 'r');
		$icerik = fread($dosya, filesize('dosyalar/'.$dosyaAdi));
		fclose($dosya);
		return boyerMooreArama($icerik, $kelime);
				
	}


	//html dosyasının içeriğini okuyup ardından içeriğini ve aranacak kelimeyi asıl arama yapacak metodumuz olan 
	// boyerMooreArama() fonksiyonuna gönderiyoruz. Burdan gelen bulunan sonuçları da fonksiyonun çağrıldığı yere geri döüyoruz.
	// Burda da html dosyaları düz metin dosyalarıdır ancak html etiketlerinden temizlenmesi gerekir.
	// bunun için okunan içerik php nin strip_tags methodundan geçirilerek html etiketlerinden arındırılır.
	// yani bufonksiyon html etiketlerini temizler düz text döner.
	// buna rağmen bazı bozuk etiketleri temizleyemeyebilir. Bunun temizlediğini varsayıruz.
	function htmlDosyaOku($dosyaAdi, $kelime){
		$icerik = file_get_contents('dosyalar/'.$dosyaAdi);
		
		return boyerMooreArama(strip_tags($icerik), $kelime);
	}


	// dananın kuyruğunun koptuğu nokta ve en önemli kısım işte burası.
	// bu method gelen metin içinde gelen kelimeyi arar ve bulduğu sonuçları bir dizide toplayarak birleştirir.
	// Bulunana sonuçlara arayüzde kullanılmak üzere kelimenin bulunduğu index numarası ve bulunma süresi micro saniye cinsinden dönülür.
	// burası detaylı bir şekilde anlaşılmalı.
	function boyerMooreArama($metin_orj, $kelime){
		// ilk adım aranacak kelimeyi ve aramanın yapılacağı metni küçük harfe çeviriyoruz.
		// bunu metni bulurken büyük küçük harfe duyarsız hale getirmeliyiz ki örneğin veri kelimesi aratıldığında
		// VERİ ya da veRİ gibi kelimelerle de eşleşsin. Bu yüzden herşeyi küçük harf yapıp eşleymeyi bunun üzerinde yapıyoruz.
		$metin = strtolower($metin_orj);
		$kelime = strtolower($kelime);

		// arama yapılacak kelimenin uzunluğu. 
		$kelime_uzunlugu = strlen($kelime);

		// içinde arama yapılacak metnin uzunluğu
		$metin_uzunlugu = strlen($metin);

		// bulduğumuz sonuçları da bu dizide toplayacağız.
		$result = array();

		// boyer moore algoritmasında kötü karakter tablosunu oluşturan method.
		kotuKarakterTablosu($kelime, $kelime_uzunlugu, $badchar);

		// her bir sonuç bulunduğunda bir sayacı artırarak diziye o kadar elemen ekleyeceğiz.
		// yani $bulunan_sonuçlar değişkeni en sonda kaç ise o kadar sonuç bulmuşuz demektir. 
		$bulunan_sonuclar = 0;

		// metin için kelime bulunduysa ilk harfin bulunduğu konumu tutacağız.
		// çünkü bulunan kelimeyi veya kelime bloğunu alırken bulunan yeri bilmemiz lazım.
		$bulunan_konum = 0;

		// metnin uzunluğundan kelimenin uzunluğu çıkarıldığında
		// örneğin 5000 harflik metinden 7 harflik kelime çıkarıldığında 
		// yani 4993. harten daha küçük(dana önceki) bir noktada sonuç bulunduysa bir aramayı tekrar çalıştır diyoruz.
		// Bunun yerine bu döngüyü bulunan_konum < metin_uzunluğu diyebilirdik. ama son kelime uzunluğundan daha az olan yerleri boşuna kontrol etmiş olurdu.
		while ($bulunan_konum <= ($metin_uzunlugu - $kelime_uzunlugu))
		{
			// her bir kelime bulma işlemi başlatılmadan micritime cinsinden zamanı alıyoruz
			// kelime bulunduğunda da tekrar microtime cinsinden zamanı alacağız.
			// bu iki zamanın farkı da bize kelimenin bulunma süresini verecektir.
			// ancak burda biz sadece arama yapmak için bütün hazırlıkları kötü harf tablosunu falan hazırlanmış varsayıyoruz.
			// yani bunun üzerine belki biraz daha zaman eklenebilir daha doğru sonuç için.
			// ancak şimdilik bunu böyle kabul ediyoruz.
			$sure_baslangic = microtime(true);
			
			// boyer moore algoritmasında kelimeyi başa yerleştirip kelimenin sonundan başlayarak eşleştirme yapıldığı için 
			// kelime uzunluğunun 1 eksiğini bir indiste tutuyoruz. 
			// burda bir eksiğini almamızın sebebi karşılaştırırken indis numarası kullanılmasıdır. indis ise 0 dan başladığı için
			// Örn. "veri yapilari" kelimelerinin uzunluğu 13 olmasına karşın. en sonraki i harfinin indis numarası 12 olur.
			// aynen array işlemlerindeki gibi. indis sıfırdan başlar.
			$j = $kelime_uzunlugu - 1;

			// şimdi aranan kelmenin son karakterinden başlayarak metin içinde karşısına gelen karkaterleri karşılatırıp
			// eğer eşitse sayacı bir eksilterek devam ediyoruz.
			// örneği "veri yapilari dersi proje odevi" metninde "ders" kelimesi aranırken en başa alt alta konur sondan her bir karakter eşleşiyorsa indis 1 azaltılır.
			while ($j >= 0 && $kelime[$j] == $metin[$bulunan_konum + $j]){
				$j--;
			}


			// indis sıfırdan küçük olduysa demekki bütün karakterler eşleşmiştir. çünkü kontolümüz, eğer karşılıklı karakterler eşitse indisi bir azalt olduğu için kelimenin tüm karakterleri eşit olduğunda ise indis sıfırdan küçük olmuş demektir.
			// dolayısıyla indis < 0 olduğu zaman sonuç bulunmuştur.
			if ($j < 0){
				// kelime tam eşleştiğine göre yeni bir microtime cinsinden zaman bulup (bitis_zamani), önceki zamanla farkını alarak işlemi ne kadar sürede gerçekleştirdiğini bulabilirz.
				// ancak unutmamak gerek bu zaman sadece eşleşmenin olduğu indisi bulmak için kullanılabilir.
				// farklılık gösterebilir.
				$sure_bitis = microtime(true);

				// artık indisli bir diziye bulunan konumu aktarabiliriz.
				$dizi[$bulunan_sonuclar] = $bulunan_konum;

				// zaman farkını bularak başka bir diziye de süreyi aktarabiliriz.
				// ancak dikkat edilmeli $dizi ile $dizi_sureler düzülerinde aynı indiste aynı sonucun verileri olmalı.
				// yani dizi[3] içinde 3. bulunan konum varken
				// dizi_sureler[3] te de 3. bulunan konumun süresisi tutulmaktadır.
				$dizi_sureler[$bulunan_sonuclar] = strval($sure_bitis - $sure_baslangic);

				// ardından aşağıdaki bulunamadı durumunda olduğu gibi bulunan konumumuzu güncelleyip bir sonraki aramanın yapılacağı yere atlama yapacağız.
				$bulunan_konum += ($bulunan_konum + $kelime_uzunlugu < $metin_uzunlugu) ? $kelime_uzunlugu - $badchar[ord($metin[$bulunan_konum + $kelime_uzunlugu])] : 1;

				// ardından bulunan_sonuclar indisimizi bir artırıyoruz.
				$bulunan_sonuclar++;
			}
			else{
				// indis sıfırlanmadıysa demekki tüm karakterler eşlemedi. 
				// öyleyse boyer moore algoritmasındaki atlama tablosu mantığından yola çıkarak bulunan_konum u güncelliyoruz.
				// ardından döngü tekrar çalıştığında bir sonraki eşleştirme bölümüne atlamış olacağız.
				$bulunan_konum += max(1, $j - $badchar[ord($metin[$bulunan_konum + $j])]);
			}
		}


		// Artık tüm aramayı yaptık. dizi[] değişkeninde bulunan sonuçların başlangıç indisleri
		// dizi_sureler[] değişkeninde de her bulunan indisin bulunma süreleri tutuluyor.
		// toplam olarak $bulunan_sonuclar değişkeninin son değeri kadar sonuç bulundu. 
		// geriye dönersek bulunan_sonuslar her sonuç bulunduğunda 1 artırılıyordı.
		// dolayısıyla ben dizi[] değişkenimin uzunluğu kadar döngü kurup içindeki verileri alabilirim.
		// ama gerek yok. zaten bulunan_sonuclarda kaç tane olduğunu tutuyorum.
		for ($j = 0; $j < $bulunan_sonuclar; $j++)
		{
			// bizim bu zamana kadar bulduğumuz kelime değil aslında kelimenin bulunduğu konum.
			// dizide 125. konumda kelime bulundu, 2534. konumda kelime bulundu gibi.
			// örneğin "veriyapilari proje odevi" kelimesinde "yapi" aranırsa bulunan konum 4 tür. 
			// geri dönerken 4. karakterden itibaren yapi nin kelime uzunluğu olan 4 karakter al ve geri dön dersek dönülen sonuç
			// "yapi" olur. ama bu pek doğru olmaz. geri dönerken kelimenin bulunduğu bütün kelime bloğunu dönmek gerek
			// yani bulunan sonuc "veriyapilari" olmalı. Buna da benzer sonuçlar diyeceğiz.

			// tüm bunları yapabilmek için önce bulunan kelimenin başlangıç noktasını alalım
			$kelime_baslangic = $dizi[$j];

			// ardından kelime uzunluğunu alalım.
			$kelime_harf_uzunlugu = $kelime_uzunlugu;


			// yukarıdaki örnekten yola çıkarak yapi kelimesi kelimenin ortasında bulunmuştu. Biz de kelimenin başına kadar gidelim
			// bunun için bir önceki harf boşluk, satırbaşı, nokta veya virgül dışında ise başlangıç noktamızı bir önceki alalım.
			// örneğin bir önceki boşluk ise orda durup artık kelime burdan başlıyor diyebilelim.
			// kelime başlangıç artık ilk bulduğumuz konum değil boşluğa veya özel karakterlere kadar gittiğimiz yer.
			while ( $metin[$kelime_baslangic-1] != ' ' &&  $metin[$kelime_baslangic-1] != "\n" &&  $metin[$kelime_baslangic-1] != '.' &&  $metin[$kelime_baslangic-1] != ',' ){
				$kelime_baslangic--;
			}
			

			// aynı şekilde eşleşen kelimenin son karakterinden bir sonraki karakteri de kontrol ediyoruz eğer boşluk veya özel karakterlerden biri değilse kelime bitmemiştir deyip sonraki harfi kontrol ediyoruz. örneğin noktageldiyse kelime bitmiştir diyebiliriz.
			while ( $metin[$kelime_baslangic+$kelime_harf_uzunlugu] != ' ' && $metin[$kelime_baslangic+$kelime_harf_uzunlugu] != "\n" && $metin[$kelime_baslangic+$kelime_harf_uzunlugu] !='.' && $metin[$kelime_baslangic+$kelime_harf_uzunlugu] != ',' )
				$kelime_harf_uzunlugu++;

			// artık aranan kelimenin doğru başlangıç noktasını ve doğru kelime uzunluğunu bulmuş olduk.
			// şimdi yapılması gereken php nin metin kesip alma fonksiyonu olan substr ile bulunan metni alıyoruz.
			// ardından ilk bulunduğu konumu ve bulunma süresinide "|" karakteri ile birleştirip result dizisine ekliyoruz.
			// arayüz tarafında bu karakterden parçalayıp yerine koyacağız.
			$result[$j] = substr($metin_orj, $kelime_baslangic, $kelime_harf_uzunlugu)."|".$kelime_baslangic."|".$dizi_sureler[$j];

			// bu son doğru sonucu bulma işlemini bir örnek üzerinden tekrar anlatayım.
			// örn: "veri yapilari proje odevi" metninde "proje" aranırsa geri "proje" bulundu deyip kelimeyi alıp döndüreceiz.
			// Örn2: "veri yapilari projesinde metin arama algoritması kullanılacak" metninde "proje" aranırsa geriye "projesinde" keimesini dönmeliyiz. yani bulunduğu kelimenin tamamını.
			// ardından arayüz tarafında örneğin "proje" arandıysa birincisi gibi aynısını döndüyse tam eşleşme diyeceğiz
			// 2. örnekteki gibiyse benzer sonuçlar diyeceğiz.
			// bu kadar :)

		}

		return $result;
	}


	// yine bu method da boyermore araması için gerekli olan kötü karakter tablosu oluşturan yardımcı bir method.
	// boyer more algoritmasının temel prensibi anlaşılınca bu methodun yaptığı iş anlaşılır.	
	// burda kötü karakter tablosu oluşturulurken bir çok örneğin aksine 
	// karakterin asci kodu ile eşleştirme yapıyoruz.
	// örneğin "veri" kelimeinde v=0, e=1, r=2 ve i=3 değerlerini bu şekilde tutmak yerine her bir karakterin asci koduna bu değerleri tutuyoruz. 
	// Yani şu şekilde 118=0 101=1 114=2 105=3
	// ascii tablsounda 255 karakter olduğu için o kadar bir array oluşturuyoruz. 
	// daha sonra aranan kelimenin her bir harfinin asci koduna değerleri atıyoruz.
	function kotuKarakterTablosu($kelime, $kelime_uzunlugu, &$badchar){
		for ($i = 0; $i < 256; $i++)
			$badchar[$i] = -1;

		for ($i = 0; $i < $kelime_uzunlugu; $i++)
			$badchar[ord($kelime[$i])] = $i;
	}

?>