# PHP実装が必要な可能性のあるスキル

## 条件付きスキル

以下のスキルは、特定の条件下でのみ効果を発揮するため、battle_engine.phpでの追加実装が必要になる可能性があります。

### 1. 対空掃射 (anti_air_barrage)
- **条件**: 相手に空カテゴリがいる場合
- **効果**: 自身の攻撃力40%アップ
- **発動率**: 100%
- **実装場所**: `calculateDamage()` 関数内で、相手の `domain_categories` に 'air' が含まれているかチェック

### 2. 戦車駆逐 (tank_destroyer)
- **条件**: 相手に陸カテゴリかつ騎兵カテゴリがいる場合
- **効果**: 自身の攻撃力40%アップ
- **発動率**: 100%
- **実装場所**: `calculateDamage()` 関数内で、相手の `domain_categories` に 'land' が含まれ、かつ `category` に 'cavalry' が含まれているかチェック

## 実装パターン例

```php
// battle_engine.php の calculateDamage() 関数内に追加

// 対空掃射スキル
foreach ($attackerEffects as $effect) {
    if ($effect['skill_key'] === 'anti_air_barrage') {
        // 相手に空カテゴリがいるかチェック
        if (isset($defenderData['domain_categories']) && in_array('air', $defenderData['domain_categories'])) {
            $attackMultiplier += $effect['effect_value'] / 100;
            $messages[] = "🎯 対空掃射！攻撃力上昇 (+{$effect['effect_value']}%)";
        }
    }
    
    if ($effect['skill_key'] === 'tank_destroyer') {
        // 相手に陸騎兵がいるかチェック
        $hasLandCavalry = false;
        if (isset($defenderData['troops'])) {
            foreach ($defenderData['troops'] as $troop) {
                if ($troop['domain_category'] === 'land' && $troop['category'] === 'cavalry') {
                    $hasLandCavalry = true;
                    break;
                }
            }
        }
        if ($hasLandCavalry) {
            $attackMultiplier += $effect['effect_value'] / 100;
            $messages[] = "🎖️ 戦車駆逐！攻撃力上昇 (+{$effect['effect_value']}%)";
        }
    }
}
```

## 既存の類似スキル

minibird_units_2026.sql で追加された以下のスキルも同様の条件付きロジックが必要です：

- **submarine_synergy** (対潜連携): 潜水艦と同時出撃でステータス2倍
- **marine_synergy** (上陸支援): 海兵隊と同時出撃でステータス3倍
- **anti_infantry_bomb** (対地爆撃): 歩兵・遠距離兵種がいる場合攻撃力50%アップ
- **strategic_bombing** (戦略爆撃): ワールドボス、放浪モンスター、対城壁戦で攻撃力50%アップ
- **air_superiority** (制空権): 空カテゴリと同時出撃で味方全体の攻撃力40%アップ

これらのスキルは既に battle_engine.php の `calculateArmyStats()` 関数内でシナジーロジックとして実装されています（559-582行目参照）。

## 注意事項

- 現在のSQLスクリプトでは、これらのスキルは通常のbuff型スキルとして定義されています
- 実際のゲームで正しく機能させるには、上記のようなPHP実装が必要です
- または、バトルシステム全体が条件付きバフを自動的に処理する仕組みになっている可能性もあります
- 実装する際は、既存のシナジーロジックを参考にしてください
