
<?php
require_once '../config.php';

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $telephone = trim($_POST['telephone']);
    
    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = "Tous les champs obligatoires doivent être remplis";
    } elseif ($password !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide";
    } else {
        if (register($nom, $prenom, $email, $password, $telephone)) {
            $success = "Inscription réussie! Vous pouvez maintenant vous connecter.";
        } else {
            $error = "Cette adresse email est déjà utilisée";
        }
    }
}

?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Creer le compte sur ChaussureShop</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="css/tel.css" />

		<link rel="stylesheet" href="fonts/material-design-iconic-font/css/material-design-iconic-font.min.css">

		<link rel="stylesheet" href="css/style.css">
		<link rel="stylesheet" href="../css/bootstrap.css">
	</head>

	<body>

		<div class="wrapper" style="background-image: url('images/bg-registration-form-2.jpg');">
			<div class="inner">

			<?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <br><a href="./" class="btn btn-success btn-sm mt-2">Se connecter</a>
                </div>
            <?php else: ?>

				<form action="" method="post">
					<h3>Formulaire d'inscription</h3>
					<div class="form-group">
						<div class="form-wrapper">
							<label for="">Nom</label>
							<input type="text" class="form-control" name="nom" required>
						</div>
						<div class="form-wrapper">
							<label for="">Prenom</label>
							<input type="text" class="form-control" name="prenom">
						</div>
					</div>
					<div class="form-group">
					<div class="form-wrapper">
						<label for="">Email</label>
						<input type="email" class="form-control" name="email" required>
					</div>
					<div class="form-wrapper">
						<label for="">Téléphone</label>
						<input id="telephone" type="tel" class="form-control" name="telephone" required>
					</div>


					</div>

					<div class="form-wrapper">
						<label for="">Mot de passe</label>
						<input type="password" id="password" class="form-control" name="password" required>
					</div>

				 <div class="checkbox">
						<label>
							<input type="checkbox" name="" id="tooglepass"> <span id="tooglepass-text"></span>
							<span class="checkmark"></span>
						</label>
					</div>


					<div class="form-wrapper">
						<label for="">Confirmation de mot de passe</label>
						<input type="password" id="confirm_password" class="form-control" name="confirm_password" required>
					</div>

					<script>
                                        const tooglepass= document.getElementById('tooglepass');
                                        const password = document.getElementById('password');
										const confirm_password = document.getElementById('confirm_password');

                                        const tooglepassText = document.getElementById('tooglepass-text');
                                         tooglepassText.textContent="Afficher le Mot de passe"


                                        tooglepass.addEventListener('click',
                                            ()=>{
                                                if (tooglepass.checked) {
                                                    password.type="text";
													confirm_password.type="text"

                                                } else{
                                                 password.type="password";
													confirm_password.type="password"
                                             }
                                              
                                            }
                                        )
                                    </script>
					<div class="checkbox">
						<label>
							<input type="checkbox" required> I caccept the Terms of Use & Privacy Policy.
							<span class="checkmark"></span>
						</label>
					</div>
					<button type="submit" name="register">S'Inscrire</button>
					<a href="../">Retour</a>
				</form>
				   <?php endif; ?>
			</div>
		</div>
		
	</body>

	<script src="../js/bootstrap.js"></script>
	<script src="js/intlTelInput.js"></script>
<script src="js/utils.js"></script>
<script>
var input = document.querySelector("#telephone");

// Initialiser avec drapeau + format automatique
var iti = window.intlTelInput(input, {
    initialCountry: "auto",
    geoIpLookup: function(callback) {
        fetch("https://ipapi.co/json/")
            .then(function(res) { return res.json(); })
            .then(function(data) { callback(data.country_code); })
            .catch(function() { callback("us"); });
    },
    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js",
});

// Lors de la soumission, récupérer le numéro complet
document.querySelector("form").addEventListener("submit", function() {
    var fullNumber = iti.getNumber();
    input.value = fullNumber; // +25712345678
});
</script>

	</html>