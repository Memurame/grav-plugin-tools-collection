<?php
namespace Grav\Plugin;
use Grav\Common\Grav;

/**
 * Funktion für Twig
 *
 * (c) 2018 Thomas Hirter
 *
 * Liest die rev-manifest.json aus und Generiert die Assets Links
 *
 * @author Thomas Hirter <t.hirter@gmail.com>
 */
class RevTwigExtension extends \Twig_Extension
{

  /**
   * @var
   */
  private $grav;

  /**
   * RevTwigExtension constructor.
   * @param $grav_instance
   */
  public function __construct($grav_instance){
    $this->grav = $grav_instance;
  }


  /**
   * {@inheritdoc}
   */

  public function getFunctions()
  {
    $function = array(
       new \Twig_SimpleFunction('rev', [$this, 'twig_rev_assets']),
    );

    return $function;
  }

  /**
   * {@inheritdoc}
   */
  public function getName()
  {
    return 'RevTwigExtension';
  }

  /**
   * @param $format
   * @param string $assets_dir
   * @return string
   */
  public function twig_rev_assets($format, $assets_dir = '/assets')
  {
    $theme_url = $this->grav['base_url'] . '/' . $this->grav['locator']->findResource('theme://', false);
    $theme_dir = $this->grav['locator']->findResource('theme://');
    $manifest_path = $theme_dir . $assets_dir . '/' . $format .'/rev-manifest.json';

    $manifest = [];
    if (file_exists($manifest_path)) {
      $manifest = json_decode(file_get_contents($manifest_path), TRUE);
    }

    $string = '';
    foreach($manifest as $key => $filename){
      $file = $theme_url . $assets_dir . '/' . $format . '/' . $filename;
      if($format == 'css'){
        $string .= '<link href="' . $file . '" rel="stylesheet" />';
      } else {
        $string .= '<script src="' . $file . '"></script>';
      }
    }
    return $string;
  }

}



