<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Worker;

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Notification;
use Friendica\Model\Post;
use Friendica\Model\Subscription as ModelSubscription;
use Friendica\Model\User;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushSubscription
{
	public static function execute(int $sid, int $nid)
	{
		Logger::info('Start', ['subscription' => $sid, 'notification' => $nid]);

		$subscription = DBA::selectFirst('subscription', [], ['id' => $sid]);
		if (empty($subscription)) {
			Logger::info('Subscription not found', ['subscription' => $sid]);
			return;
		}

		$notification = DBA::selectFirst('notification', [], ['id' => $nid]);
		if (empty($notification)) {
			Logger::info('Notification not found', ['notification' => $nid]);
			return;
		}

		$application_token = DBA::selectFirst('application-token', [], ['application-id' => $subscription['application-id'], 'uid' => $subscription['uid']]);
		if (empty($application_token)) {
			Logger::info('Application token not found', ['application' => $subscription['application-id']]);
			return;
		}

		$user = User::getById($notification['uid']);
		if (empty($user)) {
			Logger::info('User not found', ['application' => $subscription['uid']]);
			return;
		}

		$l10n = DI::l10n()->withLang($user['language']);

		$type = Notification::getType($notification);

		if (!empty($notification['actor-id'])) {
			$actor = Contact::getById($notification['actor-id']);
		}

		$body = '';

		if (!empty($notification['target-uri-id'])) {
			$post = Post::selectFirst([], ['uri-id' => $notification['target-uri-id'], 'uid' => [0, $notification['uid']]]);
			if (!empty($post['body'])) {
				$body = BBCode::toPlaintext($post['body'], false);
				$body = Plaintext::shorten($body, 160, $notification['uid']);
			}
		}

		// @todo Add a meaningful title here, see the functionality in enotify.php
		$title = '';

		$push = Subscription::create([
			'contentEncoding' => 'aesgcm',
			'endpoint'        => $subscription['endpoint'],
			'keys'            => [
				'p256dh' => $subscription['pubkey'],
				'auth'   => $subscription['secret']
			],
		]);

		$payload = [
			'access_token'      => $application_token['access_token'],
			'preferred_locale'  => $user['language'],
			'notification_id'   => $nid,
			'notification_type' => $type,
			'icon'              => $actor['thumb'] ?? '',
			'title'             => $title ?: $l10n->t('Notification from Friendica'),
			'body'              => $body ?: $l10n->t('Empty Post'),
		];

		Logger::info('Payload', ['payload' => $payload]);

		$auth = [
			'VAPID' => [
				'subject'    => DI::baseUrl()->getHostname(),
				'publicKey'  => ModelSubscription::getPublicVapidKey(),
				'privateKey' => ModelSubscription::getPrivateVapidKey(),
			],
		];

		$webPush = new WebPush($auth, [], DI::config()->get('system', 'xrd_timeout'));

		$report = $webPush->sendOneNotification($push, json_encode($payload), ['urgency' => 'normal']);

		$endpoint = $report->getRequest()->getUri()->__toString();

		if ($report->isSuccess()) {
			Logger::info('Message sent successfully for subscription', ['subscription' => $sid, 'notification' => $nid, 'endpoint' => $endpoint]);
		} else {
			Logger::info('Message failed to sent for subscription', ['subscription' => $sid, 'notification' => $nid, 'endpoint' => $endpoint, 'reason' => $report->getReason()]);
		}
	}
}