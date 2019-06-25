<?php

namespace Redditbot\Responses;


use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Redditbot\Token;

class Comments extends Collection
{

    /** @var  \Redditbot\Responses\comments\Comment[]|\Illuminate\Support\Collection */
    protected $items;

    /** @var  Token */
    protected $token;

    public function __construct ( array $data, $collectionKey = 'body', Token $token )
    {
        $this->token = $token;

        if ( array_key_exists( 'body', $data ) && $collection = @$data[ 'body' ][ 'data' ][ 'children' ] ) {
            $items = [];

            collect( array_pluck( $collection, 'data' ) )->each( function ( $comment ) use ( &$items, $token ) {
                $items[ $comment[ 'id' ] ] = ApiResponse::getInstance( 'comments\Comment', $comment, null, $token );
            } );

            $this->items = $items;
        }
        else {
            $this->items = $data;
        }
    }


    /**
     * Returns new comments since the last time this function was run
     *
     * @return Collection
     */
    public function sinceLastCheck (string $sub)
    {
        // Fetch the last time this was run, or set it to the current time
        $thisRun = $lastRun = Carbon::parse(
            Cache::remember("yomo.redditbot.lastrun.$sub", null, function() {
                return now();
            })
        );

        // The collection we will return
        $newItems = collect();

        self::each( function ( $comment ) use ( $lastRun, &$newItems, &$thisRun ) {

            // Make sure this isnt an edit
            if (isset($comment->edited) && $comment->edited === false) {
                // Lets not repeat a payment
                if (isset($comment->createdUtc) && $comment->createdUtc->greaterThan($lastRun)) {
                    // Don't react to this bot's own submissions
                    if ($comment->author !== config('redditbot.username')) {
                        // We have a valid comment
                        $newItems->put($comment->id, $comment);
                        // We want to store the last time of the comment for this subreddit
                        if ($comment->createdUtc->greaterThan($thisRun)) {
                            $thisRun = $comment->createdUtc;
                        }
                    }
                }
            }
        });

        // Update the cache if we found valid entries
        if ($newItems->count()) {
            Cache::forever("yomo.redditbot.lastrun.$sub", $thisRun);
        }

        return $newItems;
    }
}
