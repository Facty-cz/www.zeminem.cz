<!DOCTYPE html>
<meta charset="utf-8">
<meta name="robots" content="noindex">
<link rel="stylesheet" type="text/css" href="css/bootstrap.css">
<style>
	body {
		color: #333;
		background: white;
		width: 600px;
		margin: 50px auto
	}

	h1 {
		font: bold 47px/1.5 sans-serif;
		margin: .6em 0
	}

	p {
		font: 21px/1.5 Georgia, serif;
		margin: 1.5em 0
	}

	.alert {
		margin-top: 10px;
	}

	ul {
		color: red;
		font-weight: bold;
	}

	.form-control {
		position: relative;
		font-size: 16px;
		height: auto;
		padding: 10px;
		-webkit-box-sizing: border-box;
		-moz-box-sizing: border-box;
		box-sizing: border-box;
	}

	.form-control:focus {
		z-index: 2;
	}

	input[type="text"] {
		margin-bottom: -1px;
		border-bottom-left-radius: 0;
		border-bottom-right-radius: 0;
	}

	input[type="password"] {
		margin-bottom: 10px;
		border-top-left-radius: 0;
		border-top-right-radius: 0;
	}
</style>

<h1>Instalace nového blogu</h1>
<p>
	Zdá se, že tato aplikace zatím není nainstalována. Pojďme to rychle napravit. Zabere to jen malou chvíli&hellip;
</p>

<?php
$security = <<<NEON
#
# SECURITY WARNING: it is CRITICAL that this file & directory are NOT accessible directly via a web browser!
#
# If you don't protect this directory from direct web access, anybody will be able to see your passwords.
# http://nette.org/security-warning
#\n\n
NEON;
$form = new Nette\Forms\Form;
$drivers = array();
if (extension_loaded('pdo_mysql')) {
	$drivers['pdo_mysql'] = 'MySQL';
}
if (extension_loaded('pdo_pgsql')) {
	$drivers['pdo_pgsql'] = 'PostgreSQL';
}
if (empty($drivers)) {
	echo "
	<div class='alert alert-danger'>
		Nemáte nainstalovaný žádný podporovaný driver databáze. Podporované jsou:
		<ul>
			<li>pdo_mysql</li>
			<li>pdo_pgsql</li>
		</ul>
	</div>";
	exit;
}
$form->addSelect('driver', NULL, $drivers);
$form->addText('host')->setRequired()->setValue('127.0.0.1');
$form->addText('port');
$form->addText('dbname')->setRequired();
$form->addText('user')->setRequired();
$form->addPassword('pass');
$form->addSubmit('create', 'Nainstaloval tento projekt');
$form->render('begin');
$form->render('errors');
echo $form['driver']->control->setClass('form-control');
echo $form['host']->control->setClass('form-control')->setPlaceholder('Host databáze');
echo $form['port']->control->setClass('form-control')->setPlaceholder('Port databáze');
echo $form['dbname']->control->setClass('form-control')->setPlaceholder('Název databáze');
echo $form['user']->control->setClass('form-control')->setPlaceholder('Uživatel databáze');
echo $form['pass']->control->setClass('form-control')->setPlaceholder('Heslo k databázi');
echo $form['create']->control->setClass('btn btn-large btn-primary btn-block');
$form->render('end');

if ($form->isSubmitted() && $form->isValid()) {
	$vals = $form->getValues();
	try {
		file_put_contents(__DIR__ . '/../config/config.local.neon', $security . \Nette\Neon\Neon::encode(['doctrine' => [
				'host' => $vals->host,
				'port' => $vals->port ? (int)$vals->port : 80,
				'user' => $vals->user,
				'password' => $vals->pass,
				'dbname' => $vals->dbname,
				'driver' => $vals->driver,
			]]));
		ob_start();
		$config = new \Nette\Configurator();
		$container = $config->setTempDirectory(__DIR__ . '/../../temp')
			->addConfig(__DIR__ . '/../config/config.neon')
			->addConfig(__DIR__ . '/../config/config.local.neon')
			->createContainer();
		/** @var \Kdyby\Doctrine\EntityManager $em */
		$em = $container->getByType('\Kdyby\Doctrine\EntityManager');
		$schemaTool = new Doctrine\ORM\Tools\SchemaTool($em);
		$schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
//		$schemaTool->updateSchema($em->getMetadataFactory()->getAllMetadata());
		$post = new \Entity\Post;
		$post->title = 'Vítejte na svém novém blogu!';
		$post->slug = 'vitejte-na-svem-novem-blogu';
		$post->body = 'Instalace proběhla úspěšně. Jupí! (-:';
		$post->date = new \DateTime;
		$post->publish_date = new \DateTime;
		$em->persist($post);
		$em->flush();
		echo "<div class='alert alert-success'><strong>OK</strong> Instalace byla úspěšná, načtěte prosím tuto stránku znovu.</div>";
	} catch (\Exception $exc) {
		dump($exc);
		file_put_contents(__DIR__ . '/../config/config.local.neon', $security);
		echo "<div class='alert alert-danger'><strong>ERROR (#" . $exc->getCode() . "):</strong> " . $exc->getMessage() . "</div>";
	}
}

exit;
