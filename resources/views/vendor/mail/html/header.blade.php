@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ config('app.url') }}/assets/splash_white.png?v={{ file_exists(public_path('assets/splash_white.png')) ? filemtime(public_path('assets/splash_white.png')) : 1 }}" class="logo" alt="{{ config('app.name') }}" style="max-height:40px;">
</a>
</td>
</tr>
