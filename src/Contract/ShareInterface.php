<?php declare(strict_types=1);

namespace Jcsp\SocialSdk\Contract;


use Jcsp\SocialSdk\Model\Channel;
use Jcsp\SocialSdk\Model\VideoShareResult;
use Jcsp\SocialSdk\Model\VideoShareParams;

interface ShareInterface extends AuthorizationInterface, UserInterface
{

    /**
     * 是否能够分享到用户
     * @return bool
     */
    public function canShareToUser(): bool;

    /**
     * 是否能够分享到频道
     * @return bool
     */
    public function canShareToChannel(): bool;

    /**
     * 获取要分享到的频道列表
     * 这个“频道”在不同平台有不同的说法，如：channel, page, board, blog, folder 等等的说法。
     * 如果某些平台无需分享到频道，返回空数组
     * @return Channel[]
     */
    public function getShareChannelList(): array;

    /**
     * 视频分享
     * @param VideoShareParams $params
     * @return VideoShareResult
     */
    public function shareVideo(VideoShareParams $params): VideoShareResult;

    /**
     * 异步获取视频分享链接
     * @param VideoShareParams $params
     * @param VideoShareResult $result
     * @return string
     */
    public function asyncToGetUrl(VideoShareParams $params, VideoShareResult $result): string;

}