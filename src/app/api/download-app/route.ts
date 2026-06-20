import { NextResponse } from 'next/server';

export async function GET() {
  try {
    // 1. Fetch latest release from GitHub API
    // Using next cache revalidate to avoid rate limits (caches for 1 hour)
    const ghRes = await fetch('https://api.github.com/repos/bmssykharui1/bmssy/releases/latest', {
      next: { revalidate: 3600 }
    });
    
    if (!ghRes.ok) {
      return NextResponse.json({ error: 'Failed to fetch release info from secure server' }, { status: 500 });
    }

    const releaseData = await ghRes.json();
    
    // 2. Find the APK asset
    const apkAsset = releaseData.assets?.find((asset: any) => asset.name.endsWith('.apk'));
    
    if (!apkAsset) {
      return NextResponse.json({ error: 'No application package found in the latest release' }, { status: 404 });
    }

    // 3. Stream the download directly from the asset URL
    // The user's browser only sees the /api/download-app endpoint.
    const apkRes = await fetch(apkAsset.browser_download_url);
    
    if (!apkRes.ok || !apkRes.body) {
      return NextResponse.json({ error: 'Failed to stream the application file' }, { status: 500 });
    }

    // Return the stream directly to the client
    return new Response(apkRes.body as any, {
      headers: {
        'Content-Type': 'application/vnd.android.package-archive',
        'Content-Disposition': `attachment; filename="${apkAsset.name}"`,
        'Content-Length': apkAsset.size.toString()
      }
    });

  } catch (error) {
    console.error('Download Proxy Error:', error);
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 });
  }
}
