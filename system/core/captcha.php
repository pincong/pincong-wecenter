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
	private $secret;

	public function __construct()
	{
		$this->secret = AWS_APP::token()->new_secret('captcha_passphrase_' . G_COOKIE_HASH_KEY);

		$fontsize = rand_minmax(S::get('captcha_fontsize_min'), S::get('captcha_fontsize_max'), rand(20, 22));
		$wordlen = rand_minmax(S::get('captcha_wordlen_min'), S::get('captcha_wordlen_max'), 4);

		$width = rand_minmax(S::get('captcha_width_min'), S::get('captcha_width_max'), 100);
		$height = rand_minmax(S::get('captcha_height_min'), S::get('captcha_height_max'), 40);

		$dot_noise = rand_minmax(S::get('captcha_dot_noise_min'), S::get('captcha_dot_noise_max'), rand(3, 6));
		$line_noise = rand_minmax(S::get('captcha_line_noise_min'), S::get('captcha_line_noise_max'), rand(1, 2));

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
		$captcha_font_ids = S::get('captcha_font_ids');
		if (!$captcha_font_ids)
		{
			$captcha_font_ids = '1, 2, 3, 4, 5, 6, 7, 8';
		}
		$captcha_font_ids = explode(',', $captcha_font_ids);

		$font_id = intval(array_random($captcha_font_ids));
		$base_dir = AWS_PATH . 'core/fonts/';
		return $base_dir . $font_id . '.ttf';
	}

	public function generateWord()
	{
		return $this->captcha->generateWord();
	}

	public function generateToken($word, $expire)
	{
		return AWS_APP::token()->create($word, $expire, $this->secret);
	}

	public function generateImage($word)
	{
		return $this->captcha->generateImage($word);
	}

	public function is_valid($word, $token)
	{
		if (!!$word AND !!$token)
		{
			if (AWS_APP::token()->verify($word_result, $token, $this->secret))
			{
				if (strtolower($word) == strtolower($word_result))
				{
					return true;
				}
			}
		}
		return false;
	}

}
