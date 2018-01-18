<?php

namespace App\Http\Controllers;

use App\GAReportsPosts;
use Illuminate\Http\Request;

class GoogleAnalyticsReportsPostsController extends Controller
{
    public function change_post_cluster(Request $request)
    {
        $message = 'error';
        $data    = json_decode($request->getContent());
        if (empty($request->id) || empty($data->cluster) || !isset($data->value)) {
            return $message;
        }
        $post = GAReportsPosts::whereId($request->id)->first();
        
        $post->setCluster($data->cluster, $data->value);
        $message = 'success';
        
        return $message;
    }
    
    public function grab_posts(Request $request)
    {
        $url      = $_ENV['ALTOROS_BLOG_WP_REST_API_POSTS_ENDPOINT_CUSTOM'];
        $page     = 1;
        $per_page = 100;
        do {
            $content = @file_get_contents($url . "?page=$page&per_page=$per_page");
            if ($content) {
                $posts = json_decode($content, true);
                if ($posts) {
                    foreach ($posts as $post) {
                        if (GAReportsPosts::validate([
                            'post_url'   => $post['post_name'],
                            'post_name'  => $post['post_title'],
                            'post_wp_id' => $post['ID'],
                        ])
                        ) {
                            $postObj             = new GAReportsPosts();
                            $postObj->post_name  = $post['post_title'];
                            $postObj->post_url   = $post['post_name'];
                            $postObj->post_wp_id = $post['ID'];
                            $postObj->save();
                            echo $post['ID'] . '<br>';
                        }
                    }
                }
            }
            $page++;
        } while ($content);
    }
}
