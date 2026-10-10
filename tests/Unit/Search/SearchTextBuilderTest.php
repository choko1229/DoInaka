<?php

declare(strict_types=1);

use App\Services\Search\SearchTextBuilder;

it('ひらがなはカタカナに、全角英数は半角に、半角カナは全角に、英字は小文字にそろえる', function (): void {
    $builder = new SearchTextBuilder;

    expect($builder->normalize('ししまい'))->toBe('シシマイ')
        ->and($builder->normalize('ＡＢＣ１２３'))->toBe('abc123')
        ->and($builder->normalize('ｼｼﾏｲ'))->toBe('シシマイ')
        ->and($builder->normalize('Udon Shop'))->toBe('udon shop')
        ->and($builder->normalize("  獅子舞　　奉納\n\t開催 "))->toBe('獅子舞 奉納 開催');
});

it('HTML タグは取り除く', function (): void {
    expect((new SearchTextBuilder)->normalize('<p>獅子<b>舞</b></p><script>alert(1)</script>'))->toBe('獅子舞alert(1)');
});

it('空や null の部分は飛ばして、空白でつなぐ', function (): void {
    expect((new SearchTextBuilder)->build(['獅子舞', null, '', '  ', 'ししまい', '香川県']))->toBe('獅子舞 シシマイ 香川県');
});

it('「獅子舞」の検索語は、本文の「ししまい」にも当たる形になる(読みの違いを吸収する)', function (): void {
    $builder = new SearchTextBuilder;

    expect($builder->normalize('ししまい'))->toBe($builder->normalize('シシマイ'));
});
