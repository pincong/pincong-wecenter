<?php
/*
+--------------------------------------------------------------------------
|   WeCenter [#RELEASE_VERSION#]
|   ========================================
|   by WeCenter Software
|   © 2011 - 2014 WeCenter. All Rights Reserved
|   http://www.wecenter.com
|   ========================================
|   Support: WeCenter@qq.com
|
+---------------------------------------------------------------------------
*/

class core_captcha
{
	private $captcha;

	public function __construct()
	{
		if (defined('IN_SAE'))
		{
			$img_dir = SAE_TMP_PATH;
		}
		else
		{
			$img_dir = ROOT_PATH . 'cache/captcha/';

			if (!is_dir($img_dir))
			{
				@mkdir($img_dir);
			}
		}

		$fontsize = rand_minmax(get_setting('captcha_fontsize_min'), get_setting('captcha_fontsize_max'), rand(20, 22));
		$wordlen = rand_minmax(get_setting('captcha_wordlen_min'), get_setting('captcha_wordlen_max'), 4);

		$width = rand_minmax(get_setting('captcha_width_min'), get_setting('captcha_width_max'), 100);
		$height = rand_minmax(get_setting('captcha_height_min'), get_setting('captcha_height_max'), 40);

		$this->captcha = new Zend_Captcha_Image(array(
			'font' => $this->get_font(),
			'imgdir' => $img_dir,
			'fontsize' => $fontsize,
			'width' => $width,
			'height' => $height,
			'wordlen' => $wordlen,
			'session' => new Zend_Session_Namespace(G_COOKIE_PREFIX . '_Captcha'),
			'timeout' => 600
		));

		$dot_noise = rand_minmax(get_setting('captcha_dot_noise_min'), get_setting('captcha_dot_noise_max'), rand(3, 6));
		$line_noise = rand_minmax(get_setting('captcha_line_noise_min'), get_setting('captcha_line_noise_max'), rand(1, 2));

		$this->captcha->setDotNoiseLevel($dot_noise);
		$this->captcha->setLineNoiseLevel($line_noise);
	}

	public function get_font()
	{
		$captcha_font_ids = get_setting('captcha_font_ids');
		if (!$captcha_font_ids)
		{
			$captcha_font_ids = '1, 2, 3, 4, 5, 6, 7, 8';
		}
		$captcha_font_ids = explode(',', $captcha_font_ids);

		$font_id = intval(array_random($captcha_font_ids));
		$base_dir = AWS_PATH . 'core/fonts/';
		return $base_dir . $font_id . '.ttf';
	}

	public function generate()
	{
		$this->captcha->generate();

		HTTP::no_cache_header();

		readfile($this->captcha->getImgDir() . $this->captcha->getId() . $this->captcha->getSuffix());

		die;
	}

	public function is_validate($validate_code, $generate_new = true)
	{
		if (!empty($validate_code) AND strtolower($this->captcha->getWord()) == strtolower($validate_code))
		{
			if ($generate_new)
			{
				$this->captcha->generate();
			}
			
			return true;
		}

		return false;
	}
}
