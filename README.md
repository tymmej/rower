# CO TO JEST?
Przechowywanie plików gpx z wycieczek.

1. Podgląd pojedynczej trasy
   pokazuje średnią prędkość na danym dystansie dla trasy, dystans, średnią prędkość, mapę
2. Podgląd wszystkich tras
3. Kalendarz
4. Pokazywanie map na Google Maps/Satelita i OpenStreetMap
5. Podział na planowane trasy i przejechane
6. Pokazanie wszystkich tras z danego okresu na jednej mapie
7. System logowania
8. Podstawowa obsługa błędów i zabezpieczenie przed "hakerami" 
9. Skalowanie dla małych ekranów: tablet i telefon.

# JAK ZACZĄĆ:
W katalogu files są tylko pliki potrzebne do działanie (oprócz .gitignore).

Do tego potrzebny jest plik key.php ze zmienną $key z kluczem do Google API (prawdopodobnie niewymagany przy małej ilości zapytań).

Aby włączyć rejestrację usunąć user.php:270.

# TODO:

checkTryb(){
	$tryby=array('gpx', 'szlaki', 'inne');
	if(in_array($tryby, $_GET['tryb']){
		$tryb=$_GET['tryb'];
	}
	else{
		$tryb='gpx';
	}
}