<?php

namespace Redditbot;


use Redditbot\Responses\ApiResponse;


class Reddit extends RedditApiHandler
{

	/** @var \Redditbot\Token */
	protected $token;


	public function __construct ()
	{
		$this->token = new Token();
	}


	/**
	 * @return \Redditbot\Responses\me
	 */
	public function me ()
	{
		return ApiResponse::getInstance( __FUNCTION__, $this->send( self::API_BASE_URL . '/api/v1/me', $this->token ), 'body', $this->token );
	}


	/**
	 * @param string $subreddit The subreddit to retrieve comments from
	 * @param string $sortBy    Defaults to 'new', but allows 'rising', 'controversial' and 'top'
	 * @param int    $limit     How many comments to pull
	 *
	 * @return \Redditbot\Responses\Comments
	 */
	public function fetchComments ( $subreddit, $sortBy = 'new', $limit = 100 )
	{

		return ApiResponse::getInstance( 'comments', $this->send( self::API_BASE_URL . '/r/' . $subreddit
            . '/comments/?' . http_build_query( [
                'cb'    => time(),
                'sort'  => $sortBy,
                'limit' => $limit,
                'query' => 'bottle',
                'show' => 'all',
            ] ), $this->token ), null, $this->token );
	}

    /**
     * Grab info about a comment
     *
     * @param $subreddit
     * @param $id
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function fetchInfo($subreddit, $id)
    {
        return ApiResponse::getInstance('comments', $this->send(self::API_BASE_URL . '/r/' . $subreddit
            . '/api/info/?' . http_build_query([
                'cb'    => time(),
                'limit' => 1,
                'id'    => $id,
            ]), $this->token), null, $this->token);
    }
}
