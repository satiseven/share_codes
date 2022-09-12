import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import MilestoneEvent from 'App/Models/MileStoneEvent'

export default class MilestoneEventController {
  public async index({ auth }: HttpContextContract) {
    const user = auth.use('api').user!

    const milestoneEvents = await MilestoneEvent.query()
      .where({
        user_id: user.id,
      })
      .orderBy('event_type')

    return {
      data: {
        milestoneEvents: milestoneEvents.map(
          (milestoneEvent) => milestoneEvent.serializedMilestoneEventInfo
        ),
      },
    }
  }
}
