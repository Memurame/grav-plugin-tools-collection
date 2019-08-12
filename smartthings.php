<?php
namespace Grav\Plugin;

use \Grav\Common\Plugin;
use Grav\Common\Utils;
use Grav\Common\Page\Page;
use Grav\Common\Data\Blueprints;
use RocketTheme\Toolbox\Event\Event;

require_once 'adapters/imagick.php';
require_once 'adapters/gd.php';

class SmartthingsPlugin extends Plugin
{
  /**
   * @var string
   */
  protected $adapter;

  /**
   * @var array
   */
  protected $sizes;


  public function getPluginConfigKey($key = null) {
    $pluginKey = 'plugins.' . $this->name;

    return ($key !== null) ? $pluginKey . '.' . $key : $pluginKey;
  }

  public function getPluginConfigValue($key = null, $default = null) {
    return $this->config->get($this->getPluginConfigKey($key), $default);
  }

  public function getConfigValue($key, $default = null) {
    return $this->config->get($key, $default);
  }

  protected function resizeImage($source, $target, $width, $height, $quality = 95)
  {
    $adapter = $this->getImageAdapter($source);
    $adapter->resize($width, $height);
    $adapter->setQuality($quality);

    return $adapter->save($target);
  }

  protected function imageDependencyCheck($adapter = 'gd')
  {
    if ($adapter === 'gd') {
      return extension_loaded('gd');
    }

    if ($adapter === 'imagick') {
      return class_exists('\Imagick');
    }
  }

  protected function getImageAdapter($source)
  {
    $imagick_exists = $this->imageDependencyCheck('imagick');
    $gd_exists = $this->imageDependencyCheck('gd');

    if ($this->adapter === 'imagick') {
      if ($imagick_exists) {
        return new ImagickAdapter($source);
      } else if ($gd_exists) {
        return new GDAdapter($source);
      }
    } else if ($this->adapter === 'gd') {
      if ($gd_exists) {
        return new GDAdapter($source);
      } else if ($imagick_exists) {
        return new ImagickAdapter($source);
      }
    }
  }

  public static function getSubscribedEvents()
  {
    if(Utils::isAdminPlugin()){
      return [];
    }

    return [
      'onMarkdownInitialized' => ['onMarkdownInitialized', 0],
      'onPluginsInitialized' => ['onPluginsInitialized', 0],
      'onAdminSave' => ['onAdminSave', 0]
    ];


  }

  public function onPluginsInitialized()
  {
    if($this->isAdmin()){
      $this->enable([
        'onAdminTwigTemplatePaths' => ['onAdminTwigTemplatePaths', -10],
        'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
        'onAdminMenu' => ['onAdminMenu', 0],
        'onBlueprintCreated' => ['onBlueprintCreated', 0]
      ]);
      $this->registerPermissions();
    } else {
      $this->enable([
        'onTwigExtensions' => ['onTwigExtensions', -100],
        'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0]
      ]);
    }
  }

  public function onTwigExtensions()
  {

    if($this->getPluginConfigValue('filter.twigextension.enabled')) {
      $modules = $this->getPluginConfigValue('filter.twigextension.modules');
      if (in_array('intl', $modules)) {
        require_once(__DIR__ . '/vendor/Twig/Intl.php');
        $this->grav['twig']->twig->addExtension(new \Twig_Extensions_Extension_Intl());
      }
      if (in_array('array', $modules)) {
        require_once(__DIR__ . '/vendor/Twig/Array.php');
        $this->grav['twig']->twig->addExtension(new \Twig_Extensions_Extension_Array());
      }
      if (in_array('date', $modules)) {
        require_once(__DIR__ . '/vendor/Twig/Date.php');
        $this->grav['twig']->twig->addExtension(new \Twig_Extensions_Extension_Date());
      }
    }

  }

  public function onAdminTwigTemplatePaths($e) {
    $paths = $e['paths'];
    $paths[] = __DIR__ . '/admin/templates';
    $e['paths'] = $paths;
  }

  public function onTwigTemplatePaths()
  {
    $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
  }

  public function onAdminMenu()
  {
    if($this->getPluginConfigValue('infopanel.enabled')){
      $name = $this->getPluginConfigValue('infopanel.navtitel');
      $this->grav['twig']->plugins_hooked_nav[$name] = [
        'route' => 'smartthings-infopanel',
        'icon' => 'fa-info',
        'authorize' => 'smartthings.infopanel'];
    }
  }

  public function onAdminSave($event)
  {
    $page = $event['object'];

    if (!$page instanceof Page) {
      return false;
    }

    if($this->getPluginConfigValue('imageresize.enabled')) {

      $paths = $this->getPluginConfigValue('imageresize.auswahl');
      $route = explode('/', $page->route());
      $continue = false;
      $s = [];

      if($this->getPluginConfigValue('imageresize.mode') != 'all'){
        foreach($route as $index => $splitroute) {
          foreach ($paths as $path) {
            $splitpath = explode('/', $path);
            if ((count($splitpath) - 1) < $index) {
              $s[$index] = false;
              continue;
            }


            if ($splitpath[$index] == '*') {
              $continue = false;
              for ($i = 0; $i < count($route); $i++) {
                $s[$i] = true;
              }
              break;
            }
            if ($splitroute == $splitpath[$index]) {
              $s[$index] = true;
              $continue = true;
            } else {
              $s[$index] = false;
              $continue = false;
            }
          }
          if ($continue == false) {
            break;
          }

        }
      }



      if(( $this->getPluginConfigValue('imageresize.mode') == 'ignorelist' && array_sum($s) == count($s) ) ||
        ( $this->getPluginConfigValue('imageresize.mode') == 'allowlist' && array_sum($s) != count($s))){
        $this->grav['admin']->setMessage('Die Verkleinerung wurde für diesen Inhalt deaktiviert.', 'info');
        return false;

      }
      if (!$this->imageDependencyCheck('imagick') && !$this->imageDependencyCheck('gd')) {
        $this->grav['admin']->setMessage('Imagick oder GD sind nicht installiert. UM Bilder zu verkleinern muss jedoch eines davon installiert sein..', 'warning');
        return false;
      }

      $this->sizes = (array) $this->getPluginConfigValue('imageresize.sizes');
      $this->adapter = $this->getPluginConfigValue('imageresize.adapter');

      if($this->sizes){
        foreach ($page->media()->images() as $filename => $medium) {
          $srcset = $medium->srcset(false);

          if ($srcset != '') {
            continue;
          }

          $page_path = $page->path();
          $source_path = $page_path . '/' . $filename;
          $dest_path = [];
          $info = pathinfo($source_path);
          $count = 0;
          $remove_original = $this->getPluginConfigValue('imageresize.remove_original');

          foreach ($this->sizes as $sIndex => $size) {
            $count++;
            $dest_path[$sIndex] = "{$info['dirname']}/{$info['filename']}@{$count}x.{$info['extension']}";

            if ($size['width'] > $medium->width) {
              copy($source_path, $dest_path[$sIndex]);
              continue;
            }

            $width = $size['width'];
            $quality = $size['quality'];
            $height = ($width / $medium->width) * $medium->height;
            $this->resizeImage($source_path, $dest_path[$sIndex], $width, $height, $quality);
          }

          if ($count > 0) {
            $message = "$filename wurde $count mal verkleinert";
          }

          if ( !$remove_original ) {
            $message .= ' (Originales Bild NICHT gelöscht)';
          } else {
            $message .= ' (Originales Bild gelöscht)';
            unlink($source_path);
            copy($dest_path[0], $source_path);
          }

          if(!empty($message)){
            $this->grav['admin']->setMessage($message, 'info');
          }
        }
      } else {
        $message = 'Keine Grössen in den Einstellungen angegeben. Bild(er) wurden nicht verkleinert.';
        $this->grav['admin']->setMessage($message, 'error');
      }
    }
  }

  public function registerPermissions() {
    $this->grav['admin']->addPermissions(['smartthings.infopanel' => 'boolean']);
  }

  public function onBlueprintCreated(Event $event)
  {
    $newtype = $event['type'];

    if($this->getPluginConfigValue('pageblueprint.options.enabled')) {
      if (0 === strpos($newtype, 'modular/')) {
        $blueprint = $event['blueprint'];
        if ($blueprint->get('form/fields/tabs', null, '/')) {

          $blueprints = new Blueprints(__DIR__ . '/blueprints/');
          $extends = $blueprints->get('optionswithorder');
          $blueprint->extend($extends, true);

        }
      } else {
        $blueprint = $event['blueprint'];
        if ($blueprint->get('form/fields/tabs', null, '/')) {

          $blueprints = new Blueprints(__DIR__ . '/blueprints/');
          $extends = $blueprints->get('options');
          $blueprint->extend($extends, true);

        }
      }
    }
    if($this->getPluginConfigValue('pageblueprint.advanced.enabled')){
      $blueprint = $event['blueprint'];
      if ($blueprint->get('form/fields/tabs', null, '/')) {

        $blueprints = new Blueprints(__DIR__ . '/blueprints/');
        $extends = $blueprints->get('advanced');
        $blueprint->extend($extends, true);

      }
    }
  }

  public function onMarkdownInitialized(Event $event)
  {
    if ($this->getPluginConfigValue('markdown.enabled')) {
      $markdown = $event['markdown'];

      $markdown->addInlineType('{', 'ColoredText');
      $markdown->addInlineType('{', 'Cta');
      $markdown->addInlineType('{', 'TelefonLink');
      $markdown->addInlineType('{', 'MailLink');

      $markdown->inlineColoredText = function($excerpt) {

        if (preg_match('/^{color:([#\w]\w+)}([^{]+){\/color}/', $excerpt['text'], $matches))
        {

          return array(
            'extent' => strlen($matches[0]),
            'element' => array(
              'name' => 'span',
              'text' => $matches[2],
              'attributes' => array(
                'style' => 'color: '.$matches[1],
              ),
            ),
          );
        }
      };

      $markdown->inlineTelefonLink = function($excerpt) {
        if (preg_match('/^{tel:([^{]+)}([^{]+){\/tel}/', $excerpt['text'], $matches))
        {
          return array(
            'extent' => strlen($matches[0]),
            'element' => array(
              'name' => 'a',
              'text' => $matches[2],
              'attributes' => array(
                'href' => 'tel:'.$matches[1],
                'class' => 'link-telefon',
              ),
            ),
          );
        }
      };

      $markdown->inlineMailLink = function($excerpt) {

        if (preg_match('/^{mail:([^{]+)}([^{]+){\/mail}/', $excerpt['text'], $matches))
        {

          return array(
            'extent' => strlen($matches[0]),
            'element' => array(
              'name' => 'a',
              'text' => $matches[2],
              'attributes' => array(
                'href' => 'mailto:'.$matches[1],
                'class' => 'link-mail',
              ),
            ),
          );
        }
      };

      $markdown->inlineCta = function($excerpt) {

        if (preg_match('/^{cta:([^{]+)}([^{]+){\/cta}/', $excerpt['text'], $matches))
        {

          return array(
            'extent' => strlen($matches[0]),
            'element' => array(
              'name' => 'a',
              'text' => $matches[2],
              'attributes' => array(
                'href' => '#',
                'id' => $matches[1],
              ),
            ),
          );
        }
      };
    }

  }
}
