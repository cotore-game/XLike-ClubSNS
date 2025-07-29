# XLike-ClubSNS
PHP,MySQLの勉強練習のために、Xのような機能をもつプライベートSNSを作成する。

DBのパスワードを安全に取得するためにPHPではgetenv()を利用している。
ローカル(Docker)では.envをgitignoreに追加し、取得しているが本番環境ではサーバーの兼ね合いで
.htaccessに直接SetEnvする必要がある。
ここは手動で行う。
