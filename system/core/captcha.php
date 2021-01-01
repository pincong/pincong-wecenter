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
		$fontsize = rand_minmax(get_setting('captcha_fontsize_min'), get_setting('captcha_fontsize_max'), rand(20, 22));
		$wordlen = rand_minmax(get_setting('captcha_wordlen_min'), get_setting('captcha_wordlen_max'), 4);

		$width = rand_minmax(get_setting('captcha_width_min'), get_setting('captcha_width_max'), 100);
		$height = rand_minmax(get_setting('captcha_height_min'), get_setting('captcha_height_max'), 40);

		$dot_noise = rand_minmax(get_setting('captcha_dot_noise_min'), get_setting('captcha_dot_noise_max'), rand(3, 6));
		$line_noise = rand_minmax(get_setting('captcha_line_noise_min'), get_setting('captcha_line_noise_max'), rand(1, 2));

		$this->captcha = new Services_Captcha_Image();

		$this->captcha->setFont($this->get_font());
		$this->captcha->setFontSize($fontsize);
		$this->captcha->setWordlen($wordlen);

		$this->captcha->setWidth($width);
		$this->captcha->setHeight($height);

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
		$word = $this->captcha->generateWord();
		AWS_APP::session()->captcha = $word;

		HTTP::no_cache_header();

		echo $this->captcha->generateImage($word);

		die;
	}

	public function is_valid($code, $flush = true)
	{
		$result = false;
		if (!empty($code) AND strtolower(AWS_APP::session()->captcha) == strtolower($code))
		{
			$result = true;
		}

		if ($flush)
		{
			unset(AWS_APP::session()->captcha);
		}

		return $result;
	}
}
