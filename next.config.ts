import type { NextConfig } from "next";

process.env.TZ = 'Asia/Kolkata';

const nextConfig: NextConfig = {
  /* config options here */
  allowedDevOrigins: ['172.20.0.1', 'localhost', '0.0.0.0'],
  typescript: {
    ignoreBuildErrors: true,
  },
  eslint: {
    ignoreDuringBuilds: true,
  },
};

export default nextConfig;
