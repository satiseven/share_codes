import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import { filterEmailsByDate } from 'App/Helpers/array'

import { LOCAL_DATE_WITH_PARAMS, YEAR } from 'App/Helpers/date'
import { ChartData, ChartFilterRanges } from 'App/Helpers/type'
import Email from 'App/Models/Email'
import Link from 'App/Models/Link'
import ReadEmail from 'App/Models/ReadEmail'
import UnreadEmail from 'App/Models/UnreadEmail'
import ChartStatsDateRange from 'App/Services/Dashboard/ChartStatsDateRange'
import DashboardInfo from 'App/Services/Dashboard/DashboardInfo'
import GetChartStatsValidator from 'App/Validators/Dashboard/GetChartStatsValidator'
import { DateTime } from 'luxon'

export default class DashboardController {
  public async getEmailsSentToday(ctx: HttpContextContract) {
    const service = new DashboardInfo(ctx)
    const payload = await service.getEmailsSentToday()

    return {
      data: {
        count: payload.emails.length,
        emails: payload.emails.map((email) => email.serializedEmailInfo),
      },
    }
  }

  public async getEmailsSentMonth(ctx: HttpContextContract) {
    const service = new DashboardInfo(ctx)
    const payload = await service.getEmailsSentMonth()

    return {
      data: {
        count: payload.emails.length,
        emails: payload.emails.map((email) => email.serializedEmailInfo),
      },
    }
  }

  public async getAverageOpenRate(ctx: HttpContextContract) {
    const service = new DashboardInfo(ctx)
    const averageOpenRate = await service.getAverageOpenRate()

    return {
      data: {
        averageOpenRate,
      },
    }
  }

  public async getAverageLinkClickRate(ctx: HttpContextContract) {
    const service = new DashboardInfo(ctx)
    const averageLinkClickRate = await service.getAverageLinkClickRate()

    return {
      data: {
        averageLinkClickRate,
      },
    }
  }

  public async getRecentlyReadEmails(ctx: HttpContextContract) {
    const service = new DashboardInfo(ctx)
    const emails = await service.getRecentlyReadEmails()

    return {
      data: {
        count: emails.length,
        emails: emails.map((email) => email.serializedEmailInfo),
      },
    }
  }

  public async getRecentlyUnreadEmails(ctx: HttpContextContract) {
    const service = new DashboardInfo(ctx)
    const emails = await service.getRecentlyUnreadEmails()

    return {
      data: {
        count: emails.length,
        emails: emails.map((email) => email.serializedEmailInfo),
      },
    }
  }

  public async getReadEmails({ auth }: HttpContextContract) {
    const user = auth.use('api').user!
    const emails = await Email.query().where({ userId: user.id }).andHas('events')

    return {
      data: {
        count: emails.length,
        emails: emails.map((email) => email.serializedEmailInfo),
      },
    }
  }

  public async getUnreadEmails({ auth }: HttpContextContract) {
    const user = auth.use('api').user!
    const emails = await Email.query().where({ userId: user.id }).andDoesntHave('events')

    return {
      data: {
        count: emails.length,
        emails: emails.map((email) => email.serializedEmailInfo),
      },
    }
  }

  public async getSignatureClicks(ctx: HttpContextContract) {
    const service = new DashboardInfo(ctx)
    const signatures = await service.getSignatureClicks()

    return {
      data: {
        count: signatures.length,
        signatures: signatures.map((signature) => signature.serializedSignatureBasicInfo),
      },
    }
  }

  public async getPings(ctx: HttpContextContract) {
    const service = new DashboardInfo(ctx)
    const pings = await service.getPings()

    return {
      data: {
        count: pings.length,
        pings: pings.map((ping) => ping.serializedPingInfo),
      },
    }
  }

  public async getChartStats({ auth, request }: HttpContextContract) {
    const user = auth.use('api').user!
    const { range, start, end } = await request.validate(GetChartStatsValidator)
    const isCustomRange = !!(start && end)
    const ranges = new ChartStatsDateRange(isCustomRange ? ChartFilterRanges.CUSTOM : range!)
    const { startDate, endDate } = ranges.getDates(start!, end!)
    // TODO: use withCount methods to do single query and consume less memory
    // TODO optimize and Let's explore if this can be solved at database query. PR #17
    const [emailsSent, emailsRead, emailsUnread] = await Promise.all([
      Email.query()
        .where({ userId: user.id })
        .andWhereBetween('created_at', [startDate.toSQL(), endDate.toSQL()]),
      ReadEmail.query()
        .where({ userId: user.id, isDeleted: false })
        .andWhereBetween('created_at', [startDate.toSQL(), endDate.toSQL()]),
      UnreadEmail.query()
        .where({ userId: user.id, isDeleted: false })
        .andWhereBetween('created_at', [startDate.toSQL(), endDate.toSQL()]),
    ])

    const daysBetween = Math.round(endDate.diff(startDate, 'days').toObject().days!)
    const monthsBetween = Math.round(endDate.diff(startDate, 'months').toObject().months!)
    const isYearSearch = monthsBetween > 1

    const dates = Array.from(Array(isYearSearch ? monthsBetween : daysBetween)).map((_, index) => {
      const date = startDate.plus(isYearSearch ? { months: index } : { days: index })
      return date.toSQL()
    })

    const emailsSentPerDate = filterEmailsByDate(dates, emailsSent, 'emailsSentCount')
    const emailsReadPerDate = filterEmailsByDate(dates, emailsRead, 'emailsReadCount')
    const emailsUnreadPerDate = filterEmailsByDate(dates, emailsUnread, 'emailsUnreadCount')
    const emailEvents: ChartData[] = emailsSentPerDate.concat(
      emailsReadPerDate,
      emailsUnreadPerDate
    )

    const emailEventsByDate = emailEvents
      .reduce((data: ChartData[], event) => {
        const existing = data.find((e) => e.date === event.date)
        if (existing) {
          // @ts-ignore
          existing[event.type] = event.count
        } else {
          data.push({ ...event, [event.type]: event.count })
        }
        return data
      }, [])
      .map((event) => {
        const { count, type, date, ...rest } = event
        return rest
      })

    return {
      data: emailEventsByDate,
    }
  }

  public async getAverageLinkClickRatePerMonth({ auth, params }: HttpContextContract) {
    const user = auth.use('api').user!
    const { month } = params
    const monthStartDate = DateTime.local(YEAR, month).toSQL()
    const monthEndDate = DateTime.local(YEAR, month + 1).toSQL()
    const emails = await Email.query().where({ userId: user.id })
    const emailsIds = emails.map((email) => email.id)
    // TODO: use withCount methods to do single query and consume less memory
    const [links, linksClicked] = await Promise.all([
      Link.query().whereIn('email_id', emailsIds),
      Link.query()
        .whereIn('email_id', emailsIds)
        .andHas('events')
        .andWhereBetween('created_at', [monthStartDate, monthEndDate]),
    ])
    const averageLinkClickRatePerMonth = (linksClicked.length / links.length) * 100

    return {
      data: {
        year: YEAR,
        month: LOCAL_DATE_WITH_PARAMS.monthLong,
        averageLinkClickRatePerMonth,
      },
    }
  }

  public async getUnreadEmailsToday({ auth }: HttpContextContract) {
    const user = auth.use('api').user!
    const todayDate = DateTime.local(
      DateTime.local().year,
      DateTime.local().month,
      DateTime.local().day
    ).toSQL()
    const emails = await Email.query()
      .where({ userId: user.id })
      .andDoesntHave('events')
      .andWhere('created_at', '>=', todayDate)

    return {
      data: {
        count: emails.length,
        emails: emails.map((email) => email.serializedEmailInfo),
      },
    }
  }

  public async getReadEmailsToday({ auth }: HttpContextContract) {
    const user = auth.use('api').user!
    const todayDate = DateTime.local(
      DateTime.local().year,
      DateTime.local().month,
      DateTime.local().day
    ).toSQL()
    const emails = await Email.query()
      .where({ userId: user.id })
      .andHas('events')
      .andWhere('created_at', '>=', todayDate)

    return {
      data: {
        count: emails.length,
        emails: emails.map((email) => email.serializedEmailInfo),
      },
    }
  }

  public async getRecentEmails({ auth }: HttpContextContract) {
    const user = auth.use('api').user!
    const emails = await Email.query()
      .where({ userId: user.id })
      .orderBy('created_at', 'desc')
      .limit(3)

    return {
      data: {
        count: emails.length,
        emails: emails.map((email) => email.serializedEmailInfo),
      },
    }
  }

  public async getRecentReadEmails({ auth }: HttpContextContract) {
    const user = auth.use('api').user!
    const emails = await Email.query()
      .where({ userId: user.id })
      .andHas('events')
      .orderBy('created_at', 'desc')
      .limit(3)

    return {
      data: {
        count: emails.length,
        emails: emails.map((email) => email.serializedEmailInfo),
      },
    }
  }

  public async getRecentUnreadEmails({ auth }: HttpContextContract) {
    const user = auth.use('api').user!
    const emails = await Email.query()
      .where({ userId: user.id })
      .andDoesntHave('events')
      .orderBy('created_at', 'desc')
      .limit(3)

    return {
      data: {
        count: emails.length,
        emails: emails.map((email) => email.serializedEmailInfo),
      },
    }
  }

  public async getDashboardInfo(ctx: HttpContextContract) {
    const service = new DashboardInfo(ctx)

    // TODO: use withCount methods to do single query and consume less memory
    const [
      emailsSentToday,
      emailsSentThisMonth,
      averageOpenRate,
      averageLinkClickRate,
      recentlyReadEmails,
      recentlyUnreadEmails,
      signatureClicks,
      pings,
    ] = await Promise.all([
      service.getEmailsSentToday(),
      service.getEmailsSentMonth(),
      service.getAverageOpenRate(),
      service.getAverageLinkClickRate(),
      service.getRecentlyReadEmails(),
      service.getRecentlyUnreadEmails(),
      service.getSignatureClicks(),
      service.getPings(),
    ])

    return {
      data: {
        emailsSentToday: {
          count: emailsSentToday.emails.length,
          state: emailsSentToday.state,
        },
        emailsSentThisMonth: {
          count: emailsSentThisMonth.emails.length,
          state: emailsSentThisMonth.state,
        },
        averageOpenRate,
        averageLinkClickRate,
        recentlyReadEmails: recentlyReadEmails.length,
        recentlyUnreadEmails: recentlyUnreadEmails.length,
        signatureClicks: signatureClicks.length,
        pings: pings.length,
      },
    }
  }
}
