import { DateTime } from 'luxon'
import {
  BaseModel,
  BelongsTo,
  belongsTo,
  column,
  computed,
  HasMany,
  hasMany,
} from '@ioc:Adonis/Lucid/Orm'
import { isRelationshipPreloaded } from 'App/Helpers/model'
import User from './User'
import PingSequenceDetail from './PingSequenceDetail'

/**
 * TODO: Consider moving settings related fields into it's own model
 * these fields from the old codebase;
 *  recipients_timezone character varying(255),
 *  send_ping_week_days json,
 *  is_send_ping_time integer DEFAULT 1,
 *  send_ping_start_time character varying(255) DEFAULT '08:00 AM'::character varying,
 *  send_ping_end_time character varying(255) DEFAULT '10:00 AM'::character varying,
 *  is_send_ping_on_special_holidays integer DEFAULT 0,
 */

export default class PingSequence extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public name: string

  @column()
  public isDeleted: boolean = false

  @column()
  public userId: string

  @column()
  public timezone: string

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @computed()
  public get pingsCount() {
    // TODO: change this into scope, it should be aggregate query. We shouldn't need to preload relation
    isRelationshipPreloaded(this, 'pingSequenceDetails')
    return this.pingSequenceDetails.length
  }

  @computed()
  public get duration() {
    // TODO: change this into scope, it should be aggregate query. We shouldn't need to preload relation
    isRelationshipPreloaded(this, 'pingSequenceDetails')
    return this.pingSequenceDetails.reduce((sum, pingDetail) => sum + pingDetail.day, 0)
  }

  @belongsTo(() => User)
  public user: BelongsTo<typeof User>

  @hasMany(() => PingSequenceDetail)
  public pingSequenceDetails: HasMany<typeof PingSequenceDetail>

  // Fixme: neither column or computed. Also never referenced in project
  public get time() {
    return this.createdAt.toRelative()
  }

  public get serializedPingSequenceInfo() {
    const { id, name, pingsCount, timezone, userId, isDeleted, duration } = this

    return {
      id,
      name,
      pingsCount,
      timezone,
      userId,
      isDeleted,
      duration,
    }
  }
}
