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
		if (!$captcha_fonts = AWS_APP::cache()->get('captcha_fonts'))
		{
			$captcha_fonts = fetch_file_lists(AWS_PATH . 'core/fonts/');

			AWS_APP::cache()->set('captcha_fonts', $captcha_fonts, get_setting('cache_level_normal'));
		}

		return array_random($captcha_fonts);
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
