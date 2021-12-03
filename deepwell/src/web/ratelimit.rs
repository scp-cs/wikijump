/*
 * web/ratelimit.rs
 *
 * DEEPWELL - Wikijump API provider and database manager
 * Copyright (C) 2021 Wikijump Team
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

//! Rate-limiter implementation using the `governor` crate.
//!
//! Based on the implementation found in the [`tide-governor`](https://github.com/ohmree/tide-governor)
//! trait, which cannot be used here because we need to expose a hole for privileged access
//! (that is, the web server backend, as opposed to external API consumers).

use governor::state::keyed::DefaultKeyedStateStore;
use governor::{clock::DefaultClock, Quota, RateLimiter};
use std::net::IpAddr;
use std::num::NonZeroU32;
use std::sync::Arc;
use tide::utils::async_trait;
use tide::{Middleware, Next, Request, StatusCode};

lazy_static! {
    static ref CLOCK: DefaultClock = DefaultClock::default();
}

/// Tide middleware to rate-limit new requests.
///
/// Once the rate-limit has been reached, all further
/// requests will be responded to HTTP 429 (Too Many Requests)
/// and a `Retry-After` header with the amount of time
/// until the next request will be permitted.
#[derive(Debug, Clone)]
pub struct GovernorMiddleware {
    limiter: Arc<RateLimiter<IpAddr, DefaultKeyedStateStore<IpAddr>, DefaultClock>>,
}

impl GovernorMiddleware {
    pub fn per_minute(times: NonZeroU32) -> Self {
        GovernorMiddleware {
            limiter: Arc::new(RateLimiter::<IpAddr, _, _>::keyed(Quota::per_minute(
                times,
            ))),
        }
    }
}

#[async_trait]
impl<State: Clone + Send + Sync + 'static> Middleware<State> for GovernorMiddleware {
    async fn handle(&self, req: Request<State>, next: Next<'_, State>) -> tide::Result {
        // Check for privileged exemption
        if let Some(values) = req.header("X-Exempt-RateLimit") {
            if let Some(value) = values.get(0) {
                // TODO do something actually secure
                if value.as_str() == "ZZ_secret-here" {
                    tide::log::debug!("Skipping rate-limit due to exemption");
                    return Ok(next.run(req).await);
                }
            }

            tide::log::warn!("Invalid X-Exempt-RateLimit header found! {:?}", values);
        }

        // Get IP address
        // TODO

        // Check rate-limite bucket by IP address
        // TODO

        todo!();
    }
}