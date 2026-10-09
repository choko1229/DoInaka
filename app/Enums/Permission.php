<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 権限(設計書5.3)。画面やコントローラでロール名を直接比べず、必ずここの権限で判定する。
 */
enum Permission: string
{
    /** 閲覧・検索・地図 */
    case View = 'view';
    /** 投稿・修正依頼・情報提供(匿名も可) */
    case Post = 'post';
    /** 「行った!」(匿名は1日1回) */
    case Visit = 'visit';
    /** コメント・返信 */
    case Comment = 'comment';
    /** お気に入り */
    case Favorite = 'favorite';
    /** マイページ(閲覧・退会。停止中も使える) */
    case MyPage = 'my-page';
    /** 公式回答バッジ付きコメント */
    case OfficialBadge = 'official-badge';
    /** 審査・コンテンツ編集 */
    case Review = 'review';
    /** マスタ・会員管理 */
    case ManageMasters = 'manage-masters';
    /** 設定・AI・広告・更新適用 */
    case ManageSettings = 'manage-settings';

    /** 停止中の会員にはできない(投稿・情報提供・コメント・反応。設計書5.1) */
    public function blockedWhenSuspended(): bool
    {
        return in_array($this, [self::Post, self::Visit, self::Comment, self::Favorite, self::OfficialBadge, self::Review, self::ManageMasters, self::ManageSettings], true);
    }
}
